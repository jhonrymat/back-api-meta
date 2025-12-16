<?php
namespace App\Http\Controllers;
use Throwable;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Group;
use Illuminate\Bus\Batch;
use App\Models\EmailEnvio;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Jobs\SendNewsletterJob;
use App\Mail\NewsletterTestMail;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendBulkNewsletterJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendNewsletterToUserJob;
use Illuminate\Support\Facades\Cache;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = Newsletter::where('user_id', Auth::id())->orderByDesc('created_at'); // Filtrar por el usuario autenticado

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
        // Validación
        $validated = $request->validate([
            'send_type' => 'required|in:immediate,scheduled',
            'scheduled_date' => 'nullable|date|after:now',
            'email_template_id' => 'required|exists:email_templates,id',
        ]);

        // Obtener template y destinatarios
        $emailTemplate = EmailTemplate::find($validated['email_template_id']);
        $recipients = $newsletter->getRecipients();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No hay destinatarios para enviar.');
        }

        // ⚡ PASO 1: Crear registro de envío PRIMERO
        $envio = EmailEnvio::create([
            'newsletter_id' => $newsletter->id,
            'user_id' => auth()->id(),
            'numero_destinatarios' => $recipients->count(),
            'status' => 'Pendiente',
            'scheduled_at' => $validated['send_type'] === 'scheduled'
                ? Carbon::parse($validated['scheduled_date'])
                : null,
        ]);

        // ⚡ PASO 2: Crear array de jobs PRIMERO
        $jobs = [];
        foreach ($recipients as $recipient) {
            if (empty($recipient->email)) {
                continue;
            }

            $jobs[] = new SendNewsletterToUserJob(
                $recipient,
                $newsletter,
                $emailTemplate
            );
        }

        if (empty($jobs)) {
            return back()->with('error', 'No hay destinatarios válidos para enviar.');
        }

        // ⚡ PASO 3: Crear batch CON los jobs desde el inicio
        $pendingBatch = Bus::batch($jobs) // ✅ Jobs desde el principio
            ->name("Email Newsletter: {$newsletter->subject} ({$envio->id})")
            ->then(function (Batch $batch) use ($envio, $newsletter) {
                DB::table('email_envios')
                    ->where('id', $envio->id)
                    ->update([
                        'status' => 'Completado',
                        'updated_at' => now()
                    ]);

                $newsletter->update(['is_sent' => true]);

                Log::info("✅ Email batch completado", [
                    'batch_id' => $batch->id,
                    'envio_id' => $envio->id
                ]);
            })
            ->catch(function (Batch $batch, \Throwable $e) use ($envio) {
                DB::table('email_envios')
                    ->where('id', $envio->id)
                    ->update([
                        'status' => 'Completado con errores',
                        'updated_at' => now()
                    ]);

                Log::error("❌ Email batch con errores", [
                    'batch_id' => $batch->id,
                    'envio_id' => $envio->id,
                    'error' => $e->getMessage()
                ]);
            })
            ->finally(function (Batch $batch) use ($envio) {
                if ($batch->finished()) {
                    $status = $batch->failedJobs > 0 ? 'Completado con errores' : 'Completado';

                    DB::table('email_envios')
                        ->where('id', $envio->id)
                        ->where('status', 'Pendiente')
                        ->update([
                            'status' => $status,
                            'updated_at' => now()
                        ]);
                }

                Log::info("📊 Email batch finalizado", [
                    'batch_id' => $batch->id,
                    'envio_id' => $envio->id
                ]);
            })
            ->onQueue('email-queue')
            ->allowFailures()
            ->before(function (Batch $batch) use ($envio) {
                // ⚡ Guardamos el batch_id ANTES de que empiece a procesar
                $envio->batch_id = $batch->id;
                $envio->save();

                Log::info("📦 Batch creado y guardado", [
                    'batch_id' => $batch->id,
                    'envio_id' => $envio->id
                ]);
            });

        // ⚡ PASO 4: Agregar delay si es programado
        if ($validated['send_type'] === 'scheduled') {
            $pendingBatch->delay(Carbon::parse($validated['scheduled_date']));
        }

        // ⚡ PASO 5: Despachar batch
        $batch = $pendingBatch->dispatch();

        // Log de confirmación
        Log::info("Newsletter batch despachado", [
            'envio_id' => $envio->id,
            'batch_id' => $batch->id,
            'total_jobs' => count($jobs),
            'destinatarios' => $recipients->count()
        ]);

        // Respuesta al usuario
        return redirect()->route('newsletters.index')->with(
            'success',
            $validated['send_type'] === 'scheduled'
            ? "Newsletter programado para {$validated['scheduled_date']}. ID: {$envio->id}"
            : "Newsletter encolado exitosamente. ID: {$envio->id}, Emails: " . count($jobs)
        );
    }

    public function cancel(Newsletter $newsletter)
    {
        $newsletter->update(['is_cancelled' => true]);

        return redirect()->back()->with('success', 'El boletín fue cancelado correctamente.');
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

    public function count(Newsletter $newsletter, Request $request)
    {
        // Cachea 10 min para no recalcular en cada clic
        $count = Cache::remember("newsletter:{$newsletter->id}:recipients_count", 600, function () use ($newsletter) {
            $newsletterId = $newsletter->id;

            // --- Emails por TAGS ---
            // pivot newsletter<->tag: tag_newsletter (newsletter_id, tag_id)
            // pivot contacto<->tag:  contacto_tag (contacto_id, tag_id)
            // tabla contactos:       contactos (id, correo)
            $tagEmails = DB::table('tag_newsletter as tn')
                ->join('contacto_tag as ct', 'ct.tag_id', '=', 'tn.tag_id')
                ->join('contactos as c', 'c.id', '=', 'ct.contacto_id')
                ->where('tn.newsletter_id', $newsletterId)
                ->whereNotNull('c.correo')
                ->selectRaw('LOWER(c.correo) as email');

            // --- Emails por GRUPOS ---
            // Ajusta a tu modelo real:
            // Supuesto común:
            //   group_newsletter (newsletter_id, group_id)
            //   group_user_emails (group_id, user_email_id)
            //   user_emails (id, email)
            $groupEmails = DB::table('group_newsletter as gn')
                ->join('group_user_emails as gue', 'gue.group_id', '=', 'gn.group_id')   // <-- ajusta si tu pivot se llama distinto
                ->join('user_emails as ue', 'ue.id', '=', 'gue.user_email_id')          // <-- o si guardas el email directo en la pivot, usa ese campo
                ->where('gn.newsletter_id', $newsletterId)
                ->whereNotNull('ue.email')
                ->selectRaw('LOWER(ue.email) as email');

            // UNION y conteo de distintos
            $union = $tagEmails->union($groupEmails);

            return DB::query()
                ->fromSub($union, 'u')
                ->distinct('u.email')
                ->count('u.email');
        });

        return response()->json(['count' => $count]);
    }

    /**
     * API para obtener estado del envío
     */
    public function getEnvioStatus($id)
    {
        try {
            $envio = EmailEnvio::findOrFail($id);

            // Verificar autorización
            if ($envio->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $batchInfo = null;

            if ($envio->batch_id) {
                try {
                    $batch = Bus::findBatch($envio->batch_id);

                    if ($batch) {
                        $batchInfo = [
                            'total' => $batch->totalJobs,
                            'processed' => $batch->processedJobs(),
                            'pending' => $batch->pendingJobs,
                            'failed' => $batch->failedJobs,
                            'progress' => round($batch->progress(), 2),
                            'finished' => $batch->finished(),
                        ];

                        // Auto-actualizar si finalizó
                        if ($batch->finished() && $envio->status === 'Pendiente') {
                            $envio->status = $batch->failedJobs > 0
                                ? 'Completado con errores'
                                : 'Completado';
                            $envio->save();
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("No se pudo obtener batch: {$e->getMessage()}");
                }
            }

            return response()->json([
                'success' => true,
                'envio' => [
                    'id' => $envio->id,
                    'newsletter' => $envio->newsletter->subject,
                    'status' => $envio->status,
                    'destinatarios' => $envio->numero_destinatarios,
                    'batch_id' => $envio->batch_id,
                    'created_at' => $envio->created_at->format('Y-m-d H:i:s'),
                ],
                'batch' => $batchInfo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estado'
            ], 500);
        }
    }
    // show
    public function show(Newsletter $newsletter)
    {
        if ($newsletter->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para ver este boletín.');
        }

        return view('newsletters.show', compact('newsletter'));
    }

    public function masivos(Request $request)
    {
        $query = EmailEnvio::with('newsletter', 'user')
            ->where('user_id', auth()->id())
            ->latest();


        // Filtros opcionales
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('newsletter_id')) {
            $query->where('newsletter_id', $request->newsletter_id);
        }

        $envios = $query->paginate(20);

        return view('newsletters.masivos.index', compact('envios'));
    }


    /**
     * Vista de monitoreo detallado
     */
    public function monitorearEnvio($id)
    {
        $envio = EmailEnvio::with('newsletter')->findOrFail($id);

        // Verificar autorización
        if ($envio->user_id !== auth()->id()) {
            abort(403, 'No autorizado');
        }

        return view('email-envios.monitor', compact('envio'));
    }

    /**
     * Reintentar jobs fallidos de un batch
     */
    public function retryFailed($id)
    {
        try {
            $envio = EmailEnvio::findOrFail($id);

            // Verificar autorización
            if ($envio->user_id !== auth()->id()) {
                return back()->with('error', 'No autorizado');
            }

            if (!$envio->batch_id) {
                return back()->with('error', 'Este envío no tiene batch asociado');
            }

            // Obtener batch
            $batch = Bus::findBatch($envio->batch_id);

            if (!$batch) {
                return back()->with('error', 'Batch no encontrado');
            }

            // Contar jobs fallidos
            $failedCount = DB::table('failed_jobs')
                ->where('payload', 'like', "%{$envio->batch_id}%")
                ->count();

            if ($failedCount === 0) {
                return back()->with('info', 'No hay jobs fallidos para reintentar');
            }

            // Reintentar todos los fallidos de este batch
            $failedJobs = DB::table('failed_jobs')
                ->where('payload', 'like', "%{$envio->batch_id}%")
                ->get();

            $retriedCount = 0;
            foreach ($failedJobs as $failedJob) {
                try {
                    \Artisan::call('queue:retry', ['id' => $failedJob->uuid]);
                    $retriedCount++;
                } catch (\Exception $e) {
                    Log::error("Error reintentando job {$failedJob->uuid}: {$e->getMessage()}");
                }
            }

            // Actualizar status si estaba fallido
            if ($envio->status === 'Fallido' || $envio->status === 'Completado con errores') {
                $envio->status = 'Pendiente';
                $envio->save();
            }

            return back()->with('success', "Se reintentaron {$retriedCount} emails fallidos");

        } catch (\Exception $e) {
            Log::error("Error reintentando envío: {$e->getMessage()}");
            return back()->with('error', 'Error al reintentar: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar envío en progreso
     */
    public function cancelarEnvio($id)
    {
        try {
            $envio = EmailEnvio::findOrFail($id);

            // Verificar autorización
            if ($envio->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            if (!$envio->batch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay batch para cancelar'
                ], 400);
            }

            $batch = Bus::findBatch($envio->batch_id);

            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch no encontrado'
                ], 404);
            }

            if ($batch->finished()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El envío ya finalizó'
                ], 400);
            }

            // Cancelar batch
            $batch->cancel();

            // Actualizar status
            $envio->status = 'Cancelado';
            $envio->save();

            return response()->json([
                'success' => true,
                'message' => 'Envío cancelado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar'
            ], 500);
        }
    }

    /**
     * Estadísticas generales
     */
    public function estadisticas()
    {
        $stats = [
            'total' => EmailEnvio::where('user_id', auth()->id())->count(),
            'pendientes' => EmailEnvio::where('user_id', auth()->id())
                ->where('status', 'Pendiente')->count(),
            'completados' => EmailEnvio::where('user_id', auth()->id())
                ->where('status', 'Completado')->count(),
            'fallidos' => EmailEnvio::where('user_id', auth()->id())
                ->whereIn('status', ['Fallido', 'Completado con errores'])->count(),
            'total_emails' => EmailEnvio::where('user_id', auth()->id())
                ->sum('numero_destinatarios'),
        ];

        return response()->json($stats);
    }

}
