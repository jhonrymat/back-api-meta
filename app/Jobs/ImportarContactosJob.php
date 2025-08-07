<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Imports\ContactosImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\ValidationException;


class ImportarContactosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $timeout = 300;

    protected $filePath;
    protected $userId;

    public function __construct($filePath, $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }


    public function handle()
    {
        try {
            $importador = new ContactosImport($this->userId);
            Excel::import($importador, $this->filePath);

            if (!empty($importador->getFilasOmitidas())) {
                Storage::put("importaciones/omitidas_{$this->userId}.json", json_encode($importador->getFilasOmitidas()));
            }

        } catch (ValidationException $e) {
            Storage::put("importaciones/errores_{$this->userId}.json", json_encode($e->errors()));
            throw $e;
        }

        Storage::delete($this->filePath);
    }
}
