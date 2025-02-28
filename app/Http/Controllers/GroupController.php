<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\UserEmail;
use App\Jobs\SendEmailJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class GroupController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Group::where('user_id', $user->id); // Filtra los grupos del usuario autenticado


        // Verifica si hay un término de búsqueda
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagina los resultados
        $groups = $query->withCount('userEmails')->paginate(10);

        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        return view('groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $user = Auth::user();

        $group = new Group($validated);
        $group->user_id = $user->id; // Asigna el grupo al usuario autenticado
        $group->save();
        return redirect()->route('groups.index')->with('success', 'Grupo creado correctamente.');
    }


    public function edit($id)
    {
        $group = Group::where('id', $id)->where('user_id', Auth::id())->firstOrFail(); // Verifica que el grupo pertenezca al usuario
        return view('groups.edit', compact('group'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group = Group::where('id', $id)->where('user_id', Auth::id())->firstOrFail(); // Asegurar que el grupo es del usuario
        $group->update($validated);

        return redirect()->route('groups.index')->with('success', 'Grupo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $group = Group::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $group->delete();

        return redirect()->route('groups.index')->with('success', 'Grupo eliminado correctamente.');
    }

    public function showEmails($id, Request $request)
    {
        $group = Group::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $query = $group->userEmails();

        if ($request->filled('search')) {
            $query->where('email', 'like', '%' . $request->search . '%')
                ->orWhere('name', 'like', '%' . $request->search . '%');
        }

        $recipients = $query->paginate(20);

        return view('groups.emails', compact('group', 'recipients'));
    }




    public function addRecipient(Request $request, $groupId)
    {
        

        $group = Group::findOrFail($groupId);
        

        if ($request->input('method') === 'file') {
            

            // Validación del archivo
            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
            ]);

            $file = $request->file('file');
            $path = $file->getRealPath();

            if (!file_exists($path) || !is_readable($path)) {
                
                return redirect()->back()->with('error', 'El archivo no se puede leer.');
            }

            $delimiter = $this->detectDelimiter($path);
            

            $data = [];
            if (($handle = fopen($path, 'r')) !== false) {
                while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                    $data[] = $row;
                }
                fclose($handle);
            }

            if (empty($data) || count($data) < 2) {
                
                return redirect()->back()->with('error', 'El archivo CSV está vacío o no tiene datos válidos.');
            }

            // Limpieza de encabezados (eliminamos BOM si existe)
            $headers = array_shift($data);
            $headers[0] = str_replace("\xEF\xBB\xBF", '', $headers[0]); // Elimina BOM
            $headers = array_map('trim', $headers);
            

            $errors = [];
            $importedCount = 0;

            foreach ($data as $index => $row) {
                

                $row = array_map('trim', $row);
                $row = array_filter($row);

                if (count($row) !== count($headers)) {
                    
                    $errors[] = [
                        'row' => $index + 2,
                        'errors' => ['La fila tiene un número incorrecto de columnas.'],
                    ];
                    continue;
                }

                $row = array_combine($headers, $row);
                

                // Validación
                $validator = Validator::make($row, [
                    'email' => 'required|email|unique:user_emails,email',
                    'name' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    
                    $errors[] = [
                        'row' => $index + 2,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                // Crear destinatario si no existe y asociarlo al grupo
                $validated = $validator->validated();
                $recipient = UserEmail::firstOrCreate(['email' => $validated['email']], $validated);
                

                $group->userEmails()->syncWithoutDetaching([$recipient->id]);
                
                $importedCount++;
            }

            if (!empty($errors)) {
                $errorFile = 'errors_' . now()->timestamp . '.csv';
                $errorPath = storage_path("app/public/$errorFile");

                $handle = fopen($errorPath, 'w');
                fputcsv($handle, ['Fila', 'Errores']);

                foreach ($errors as $error) {
                    fputcsv($handle, [$error['row'], implode(', ', $error['errors'])]);
                }

                fclose($handle);
                

                return redirect()->back()
                    ->with('errors', $errors)
                    ->with('errorFile', "/storage/$errorFile")
                    ->with('success', "$importedCount destinatarios importados correctamente. Algunos registros tienen errores.");
            }

            

            return redirect()->back()->with('success', 'Todos los destinatarios fueron importados correctamente.');
        }

        
        return redirect()->back()->with('error', 'Método no válido.');
    }

    function detectDelimiter($filePath)
    {
        $delimiters = [",", ";", "\t"]; // Detectamos comas, punto y coma, y tabulaciones
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!$lines || count($lines) < 2) {
            return ",";
        }

        $firstLine = $lines[0];
        $delimiterCounts = [];

        foreach ($delimiters as $delimiter) {
            $delimiterCounts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        // El delimitador que más repeticiones tenga es el correcto
        return array_search(max($delimiterCounts), $delimiterCounts);
    }





    public function editRecipient($groupId, $recipientId)
    {
        $group = Group::where('id', $groupId)->where('user_id', Auth::id())->firstOrFail();
        $recipient = UserEmail::findOrFail($recipientId);

        return view('groups.editRecipient', compact('group', 'recipient'));
    }

    public function updateRecipient(Request $request, $groupId, $recipientId)
    {
        $group = Group::where('id', $groupId)->where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:user_emails,email,' . $recipientId,
        ]);

        $recipient = UserEmail::findOrFail($recipientId);
        $recipient->update($validated);

        return redirect()->route('groups.showEmails', $groupId)->with('success', 'Destinatario actualizado con éxito.');
    }

    public function removeRecipient($groupId, $recipientId)
    {
        $group = Group::where('id', $groupId)->where('user_id', Auth::id())->firstOrFail();
        $recipient = UserEmail::findOrFail($recipientId);

        $group->userEmails()->detach($recipient->id);
        $recipient->delete();

        return redirect()->back()->with('success', 'Destinatario eliminado del grupo.');
    }


}
