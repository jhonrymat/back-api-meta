<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\EmailTemplate;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $templates = EmailTemplate::where('user_id', $user->id)->get(); // Solo las plantillas del usuario autenticado
        return view('email_templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('email_templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'card_background_color' => 'required|string|max:7',
            'header_color' => 'required|string|max:7',
            'footer_color' => 'required|string|max:7',
            'title' => 'nullable|string',
            'footer_text' => 'nullable|string',
        ]);

        $user = Auth::user();

        $template = new EmailTemplate($validated);
        $template->user_id = $user->id;

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $template->logo = $path;
        }

        $template->save();

        return redirect()->route('email-templates.index')->with('success', 'Plantilla creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function previewEmail(Request $request)
    {
        // Buscar la plantilla con el ID proporcionado
        $emailTemplate = EmailTemplate::find($request->id);

        if (!$emailTemplate) {
            return response("<p class='text-center text-danger'>No se encontró la plantilla.</p>", 404);
        }

        // Simular un newsletter de prueba (si no tienes un modelo, puedes usar un objeto vacío)
        $newsletter = new \stdClass();
        $newsletter->subject = "Asunto de Prueba";

        // Reemplazar variables en el contenido de la plantilla
        $content = $emailTemplate->html_content;

        return view('emails.newsletter', compact('newsletter', 'content', 'emailTemplate'))->render();
    }

    private function replaceDynamicVariables($content, $data)
    {
        foreach ($data as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }
        return $content;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        return view('email_templates.edit', compact('emailTemplate'));
    }

    /**
     * Actualizar plantilla existente.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'html_content' => 'required|string',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'card_background_color' => 'nullable|string|max:7',
            'header_color' => 'nullable|string|max:7',
            'footer_color' => 'nullable|string|max:7',
            'title' => 'nullable|string',
            'footer_text' => 'nullable|string',
        ]);

        // Actualizar valores
        $emailTemplate->fill($validated);

        // Manejo del logo
        if ($request->hasFile('logo')) {
            if ($emailTemplate->logo) {
                Storage::disk('public')->delete($emailTemplate->logo);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $emailTemplate->logo = $path;
        }

        $emailTemplate->save();

        return redirect()->route('email-templates.index')->with('success', 'Plantilla actualizada exitosamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailTemplate $emailTemplate)
    {

        try {
            if ($emailTemplate->logo) {
                Storage::disk('public')->delete($emailTemplate->logo);
            }
        } catch (\Exception $e) {
            // Manejo del error, loguea o muestra un mensaje al usuario
            return redirect()->back()->withErrors('No se pudo eliminar el archivo anterior.');
        }

        $emailTemplate->delete();

        return redirect()->route('email-templates.index')->with('success', 'Plantilla eliminada exitosamente.');
    }
}
