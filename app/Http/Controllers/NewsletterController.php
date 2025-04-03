<?php
namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Group;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Jobs\SendNewsletterJob;
use App\Mail\NewsletterTestMail;
use App\Jobs\SendBulkNewsletterJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = Newsletter::where('user_id', Auth::id()); // Filtrar por el usuario autenticado

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $newsletters = $query->paginate(10);

        $emailTemplates = EmailTemplate::where('user_id', Auth::id())->get();

        return view('newsletters.index', compact('newsletters', 'emailTemplates'));
    }
    public function create()
    {
        // usuario logueado
        $user = Auth::user();
        if (!$user) {
            Log::error('Usuario no autenticado intentando acceder a contactos.');
            return response()->json(['error' => 'Usuario no autenticado.'], 401);
        }
        $tags = $user->tags()->get();

        $groups = $user->groups()->get();

        return view('newsletters.create', compact('groups', 'tags'));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'copy_email' => 'nullable|email|max:255',
            'attachment' => 'nullable|file|mimes:pdf|max:10240', // Solo PDF de hasta 10 MB
            'content' => 'required|string',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $newsletter = new Newsletter($validated);
        $newsletter->user_id = Auth::id();
        $newsletter->save();

        // Sincronizar grupos seleccionados
        if (!empty($validated['groups'])) {
            $newsletter->groups()->sync($validated['groups']);
        }

        // Sincronizar etiquetas seleccionadas
        if (!empty($validated['tags'])) {
            $newsletter->tags()->sync($validated['tags']);
        }

        // Manejar archivo adjunto
        if ($request->hasFile('attachment')) {
            $originalName = $request->file('attachment')->getClientOriginalName();
            $timestamp = now()->format('Ymd_His'); // Formato: AñoMesDía_HoraMinutoSegundo
            $newFileName = $timestamp . '_' . $originalName; // Combinar fecha y nombre original
            $path = $request->file('attachment')->storeAs('attachments', $newFileName, 'public');
            $newsletter->has_attachment = true;
            $newsletter->attachment_path = $path;
            $newsletter->save();
        }
        return redirect()->route('newsletters.index')->with('success', 'Boletín creado exitosamente.');
    }

    public function edit(Newsletter $newsletter)
    {
        if ($newsletter->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar este boletín.');
        }

        $groups = Auth::user()->groups()->get();
        $tags = Auth::user()->tags()->get();

        return view('newsletters.edit', compact('newsletter', 'groups', 'tags'));
    }

    public function update(Request $request, Newsletter $newsletter)
    {
        if ($newsletter->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para actualizar este boletín.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'copy_email' => 'nullable|email|max:255',
            'attachment' => 'nullable|file|mimes:pdf|max:2048',
            'content' => 'required|string',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:groups,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $newsletter->fill($validated);

        // Sincronizar grupos (o desasociar todos si no se selecciona ninguno)
        if ($request->has('groups')) {
            $newsletter->groups()->sync($validated['groups']);
        } else {
            $newsletter->groups()->detach();
        }

        // Sincronizar etiquetas (o desasociar todas si no se selecciona ninguna)
        if ($request->has('tags')) {
            $newsletter->tags()->sync($validated['tags']);
        } else {
            $newsletter->tags()->detach();
        }

        // Manejar archivo adjunto si se sube uno nuevo
        if ($request->hasFile('attachment')) {
            // Eliminar archivo anterior si existe
            if ($newsletter->attachment_path) {
                \Storage::disk('public')->delete($newsletter->attachment_path);
            }
            // Subir el nuevo archivo con un nombre único basado en fecha y hora
            $originalName = $request->file('attachment')->getClientOriginalName();
            $timestamp = now()->format('Ymd_His');
            $newFileName = $timestamp . '_' . $originalName;
            $path = $request->file('attachment')->storeAs('attachments', $newFileName, 'public');
            $newsletter->has_attachment = true;
            $newsletter->attachment_path = $path;
        }

        $newsletter->save();

        return redirect()->route('newsletters.index')->with('success', 'Boletín actualizado exitosamente.');
    }


    public function destroy(Newsletter $newsletter)
    {
        if ($newsletter->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para eliminar este boletín.');
        }
        // Verificar y eliminar el archivo adjunto si existe
        if ($newsletter->attachment_path) {
            \Storage::disk('public')->delete($newsletter->attachment_path);
        }

        // Eliminar las relaciones con grupos y etiquetas
        $newsletter->groups()->detach();
        $newsletter->tags()->detach();

        // Eliminar el boletín de la base de datos
        $newsletter->delete();

        return redirect()->route('newsletters.index')->with('success', 'Boletín eliminado exitosamente.');
    }
    public function sendTest(Request $request, Newsletter $newsletter)
    {
        [$emailTemplate, $recipients] = $this->prepareNewsletterData($request, $newsletter);

        $testEmail = $request->validate(['test_email' => 'required|email'])['test_email'];
        $testName = $request->validate(['test_name' => 'required|string'])['test_name'];

        $content = str_replace(
            ['{{nombre}}', '{{email}}'],
            [$testName, $testEmail],
            $newsletter->content
        );

        Mail::to($testEmail)->send(new NewsletterTestMail($newsletter, $content, $emailTemplate));

        return redirect()->route('newsletters.index')->with('success', 'El boletín de prueba se envió correctamente.');
    }




    public function send(Request $request, Newsletter $newsletter)
    {
        // Validar la entrada del formulario
        $validated = $request->validate([
            'send_type' => 'required|in:immediate,scheduled',
            'scheduled_date' => 'nullable|date|after:now',
            'email_template_id' => 'required|exists:email_templates,id',
        ]);

        // Obtener la plantilla de correo
        $emailTemplate = EmailTemplate::find($validated['email_template_id']);

        // Verificar si hay destinatarios
        // Mover la lógica de obtener los destinatarios a la cola
        $sendType = $validated['send_type'];

        // Si es un envío programado, retrasar el trabajo
        if ($sendType === 'scheduled') {
            $scheduledDate = Carbon::parse($validated['scheduled_date']);
            dispatch(new SendBulkNewsletterJob($newsletter, $emailTemplate))->delay($scheduledDate)->onQueue('email-queue');
        } else {
            // Enviar de inmediato
            Log::info('Enviando boletín inmediatamente.');
            dispatch(new SendBulkNewsletterJob($newsletter, $emailTemplate))->onQueue('email-queue');
        }

        // Retorno con mensaje de éxito según el tipo de envío
        return redirect()->route('newsletters.index')->with(
            'success',
            $sendType === 'scheduled'
            ? 'El boletín ha sido programado.'
            : 'El boletín se está enviando.'
        );
    }









    public function countRecipients(Newsletter $newsletter)
    {
        return response()->json(['count' => $newsletter->getRecipients()->count()]);
    }

    private function prepareNewsletterData(Request $request, Newsletter $newsletter)
    {
        $request->validate([
            'email_template_id' => 'required|exists:email_templates,id',
        ]);

        $emailTemplate = EmailTemplate::findOrFail($request->email_template_id);
        $recipients = $newsletter->getRecipients();

        if ($recipients->isEmpty()) {
            return redirect()->back()->with('warning', 'No hay destinatarios para este boletín.');
        }

        return [$emailTemplate, $recipients];
    }

}
