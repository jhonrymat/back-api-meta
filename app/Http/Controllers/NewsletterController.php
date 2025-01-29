<?php
namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Group;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use App\Jobs\SendNewsletterJob;
use App\Mail\NewsletterTestMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = Newsletter::query();
        // Filtro por nombre (si aplica)
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        // Paginación (10 boletines por página)
        $newsletters = $query->paginate(10);
        return view('newsletters.index', compact('newsletters'));
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

        $groups = Group::all();
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
        $groups = Group::all();
        $tags = Auth::user()->tags()->get(); // Obtener etiquetas asociadas al usuario
        return view('newsletters.edit', compact('newsletter', 'groups', 'tags'));
    }

    public function update(Request $request, Newsletter $newsletter)
    {
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
        $request->validate([
            'test_email' => 'required|email', // Validar el correo ingresado
            'test_name' => 'required|string',
        ]);

        $testEmail = $request->test_email;
        $testName = $request->test_name;

        // Generar contenido con variables dinámicas reemplazadas para prueba
        $content = str_replace(
            ['{{nombre}}', '{{email}}'],
            [$testName, $testEmail], 
            $newsletter->content
        );

        // Enviar correo usando colas con el contenido dinámico generado
        Mail::to($testEmail)->send(new NewsletterTestMail($newsletter, $content));

        return redirect()->route('newsletters.index')->with('success', 'El boletín de prueba se envió correctamente.');
    }

    public function send(Request $request, Newsletter $newsletter)
    {
        $validated = $request->validate([
            'send_type' => 'required|in:immediate,scheduled',
            'scheduled_date' => 'nullable|date|after:now',
        ]);
        if ($validated['send_type'] === 'immediate') {
            // Enviar inmediatamente
            dispatch(new SendNewsletterJob($newsletter));
            return redirect()->route('newsletters.index')->with('success', 'El boletín se está enviando.');
        } else {
            // Programar envío
            $scheduledDate = Carbon::parse($validated['scheduled_date']);
            dispatch(new SendNewsletterJob($newsletter))->delay($scheduledDate);
            return redirect()->route('newsletters.index')->with('success', 'El boletín ha sido programado.');
        }
    }
}
