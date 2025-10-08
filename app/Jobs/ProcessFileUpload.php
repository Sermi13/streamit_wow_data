<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Filemanager\Models\Filemanager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $filemanager;
    public $filePath;
    public $diskType;

    /**
     * Create a new job instance.
     */
    public function __construct(Filemanager $filemanager, $filePath, $diskType)
    {
        $this->filemanager = $filemanager;
        $this->filePath = $filePath;
        $this->diskType = $diskType;
    }

    /**
     * Execute the job.
     */
   public function handle()
{
    try {
        // Verifica se o arquivo existe no path informado
        if (!Storage::exists($this->filePath)) {
            throw new \Exception("File does not exist at path: {$this->filePath}");
        }

        // Obtém o conteúdo do arquivo
        $file = Storage::get($this->filePath);

        // Define o caminho de destino
        $folderPath = 'streamit-laravel/' . $this->filemanager->file_name;

        // Salva o arquivo no disco apropriado
        if ($this->diskType === 'local') {
            $this->filemanager
                ->addMediaFromString($file)
                ->usingFileName($this->filemanager->file_name)
                ->toMediaCollection('filemanager');

            Storage::disk('public')->put($folderPath, $file);
        } else {
            Storage::disk($this->diskType)->put($folderPath, $file);
        }

        // Atualiza o caminho do arquivo no banco
        $this->filemanager->file_url = $folderPath;
        $this->filemanager->save();

        // Remove o arquivo temporário
        Storage::delete($this->filePath);

        // Limpa cache (opcional, dependendo da necessidade)
        Artisan::call('config:clear');
        Artisan::call('cache:clear');

    } catch (\Exception $e) {
        throw $e;
    }
}
}
