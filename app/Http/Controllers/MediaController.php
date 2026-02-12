<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::with('user')
            ->where('user_id', auth()->id())
            ->recent();

        // Filtros
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('file_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type')) {
            $type = $request->type;
            switch ($type) {
                case 'image':
                    $query->where('mime_type', 'like', 'image/%');
                    break;
                case 'video':
                    $query->where('mime_type', 'like', 'video/%');
                    break;
                case 'audio':
                    $query->where('mime_type', 'like', 'audio/%');
                    break;
                case 'document':
                    $query->whereIn('extension', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
                    break;
            }
        }

        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        $media = $query->paginate(24);

        return view('media.index', compact('media'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:51200', // 50MB max
        ]);

        $uploaded = [];

        foreach ($request->file('files') as $file) {
            $media = $this->storeMedia($file);
            $uploaded[] = $media;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'media' => $uploaded,
            ]);
        }

        return redirect()->back()->with('success', count($uploaded) . ' archivo(s) subido(s) exitosamente.');
    }

    protected function storeMedia($file)
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Generar nombre único
        $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME))
            . '-' . time()
            . '.' . $extension;

        // Determinar carpeta según tipo
        $folder = $this->getFolderByMimeType($mimeType);

        // Guardar archivo
        $path = $file->storeAs($folder, $fileName, 'public');

        // Extraer metadata
        $metadata = $this->extractMetadata($file, $mimeType);

        // Crear registro
        $media = Media::create([
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'file_name' => $originalName,
            'mime_type' => $mimeType,
            'disk' => 'public',
            'path' => $path,
            'size' => $size,
            'extension' => $extension,
            'metadata' => $metadata,
            'user_id' => auth()->id(),
        ]);

        return $media;
    }

    protected function getFolderByMimeType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/'))
            return 'media/images';
        if (str_starts_with($mimeType, 'video/'))
            return 'media/videos';
        if (str_starts_with($mimeType, 'audio/'))
            return 'media/audios';
        return 'media/files';
    }

    protected function extractMetadata($file, $mimeType)
    {
        $metadata = [];

        // Para imágenes, extraer dimensiones
        if (str_starts_with($mimeType, 'image/')) {
            try {
                $image = getimagesize($file->getRealPath());
                $metadata['width'] = $image[0] ?? null;
                $metadata['height'] = $image[1] ?? null;
            } catch (\Exception $e) {
                // Ignorar errores
            }
        }

        return $metadata;
    }

    public function show(Media $media)
    {
        // Verificar que el medio pertenece al usuario actual
        if ($media->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para ver este medio');
        }

        return view('media.show', compact('media'));
    }

    public function update(Request $request, Media $media)
    {
        // Verificar que el medio pertenece al usuario actual
        if ($media->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para actualizar este medio');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $media->update([
            'name' => $request->name,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'media' => $media->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Medio actualizado correctamente.');
    }

    public function destroy(Media $media)
    {
        // Verificar que el medio pertenece al usuario actual
        if ($media->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para eliminar este medio');
        }

        $media->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Medio eliminado correctamente.',
            ]);
        }

        return redirect()->route('media.index')->with('success', 'Medio eliminado correctamente.');
    }

    public function download(Media $media)
    {
        return $media->download();
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:media,id',
        ]);

        Media::whereIn('id', $request->ids)->each(function ($media) {
            $media->delete();
        });

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' medio(s) eliminado(s).',
        ]);
    }
}
