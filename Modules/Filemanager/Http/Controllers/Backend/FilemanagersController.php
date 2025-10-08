<?php

namespace Modules\Filemanager\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use Modules\Filemanager\Models\Filemanager;
use Illuminate\Http\Request;
use Modules\Filemanager\Http\Requests\FilemanagerRequest;
use App\Trait\ModuleTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ProcessFileUpload;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;

class FilemanagersController extends Controller
{
    protected string $exportClass = '\App\Exports\FilemanagerExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'filemanager.title',
            'media',
            'fa-solid fa-clipboard-list'
        );
    }

    public function index(Request $request)
    {
        $module_action = 'List';
        $searchQuery = $request->get('query');
        $perPage = 27;
        $page = $request->get('page', 1);

        $result = getMediaUrls($searchQuery, $perPage, $page);
        $mediaUrls = $result['mediaUrls'];
        $hasMore = $result['hasMore'];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('filemanager::backend.filemanager.partial', compact('mediaUrls'))->render(),
                'hasMore' => $hasMore,
            ]);
        }

        return view('filemanager::backend.filemanager.index', compact('module_action', 'mediaUrls', 'hasMore'));
    }

    public function getMediaStore(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = 27;
        $searchQuery = $request->get('query');
        $result = getMediaUrls($searchQuery, $perPage, $page);

        $mediaUrls = $result['mediaUrls'];
        $hasMore = $result['hasMore'];

        $html = view('filemanager::backend.filemanager.partial', compact('mediaUrls'))->render();

        return response()->json([
            'html' => $html,
            'hasMore' => $hasMore,
        ]);
    }

    public function store(FilemanagerRequest $request)
    {
        $jobs = [];

        foreach ($request->file('file_url') as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $sanitizedBaseName = str_replace([' ', '-', '.', '%20'], '_', $baseName);
            $uniqueFileName = $sanitizedBaseName . '_' . uniqid() . '.' . $extension;

            $temporaryPath = $file->storeAs('temp', $uniqueFileName);

            Log::info('[STORE] Arquivo recebido e armazenado na temp.', [
                'original' => $originalName,
                'salvo_como' => $temporaryPath
            ]);

            $filemanager = Filemanager::create([
                'file_url' => $temporaryPath,
                'file_name' => $uniqueFileName,
            ]);

            $diskType = env('ACTIVE_STORAGE', 'local');
            $job = new ProcessFileUpload($filemanager, $temporaryPath, $diskType);
            $jobs[] = $job;
        }

        $batch = Bus::batch($jobs)->dispatch();

        Log::info('[STORE] Jobs de upload enfileirados.', [
            'quantidade' => count($jobs)
        ]);

        $message = trans('filemanager.file_added');
        return redirect()->route('backend.media-library.index')->with('success', $message);
    }

    public function upload(Request $request)
    {
        try {
            Log::info('[UPLOAD] Requisição recebida.', [
                'file_name' => $request->input('file_name'),
                'index' => $request->input('index'),
                'total_chunks' => $request->input('total_chunks'),
            ]);

            $fileChunk = $request->file('file_chunk');
            $fileName = $request->input('file_name');
            $index = (int) $request->input('index');
            $totalChunks = (int) $request->input('total_chunks');

            if (!$fileChunk || !$fileName || !$totalChunks) {
                Log::warning('[UPLOAD] Parâmetros ausentes ou chunk inválido.', [
                    'fileChunk' => $fileChunk,
                    'fileName' => $fileName,
                    'totalChunks' => $totalChunks,
                ]);
                return response()->json(['success' => false, 'message' => 'Parâmetros inválidos'], 400);
            }

            $chunkDir = storage_path('app/temp/uploads/');
            if (!is_dir($chunkDir)) {
                mkdir($chunkDir, 0775, true);
                Log::info("[UPLOAD] Diretório criado: $chunkDir");
            }

            $chunkFilename = $fileName . '_part_' . $index;
            $chunkPath = $chunkDir . $chunkFilename;

            $fileChunk->move($chunkDir, $chunkFilename);
            Log::info("[UPLOAD] Chunk salvo:", ['path' => $chunkPath]);

            if ($index + 1 === $totalChunks) {
                Log::info("[UPLOAD] Último chunk recebido. Iniciando montagem do arquivo final.");

                $finalRelativePath = 'public/streamit-laravel/' . $fileName;
                $finalFullPath = storage_path('app/' . $finalRelativePath);
                $finalDir = dirname($finalFullPath);

                if (!is_dir($finalDir)) {
                    mkdir($finalDir, 0775, true);
                    Log::info("[UPLOAD] Diretório final criado: $finalDir");
                }

                $outputFile = fopen($finalFullPath, 'ab');
                for ($i = 0; $i < $totalChunks; $i++) {
                    $partPath = $chunkDir . $fileName . '_part_' . $i;
                    if (file_exists($partPath)) {
                        $chunk = fopen($partPath, 'rb');
                        stream_copy_to_stream($chunk, $outputFile);
                        fclose($chunk);
                        unlink($partPath);
                        Log::info("[UPLOAD] Chunk adicionado ao final:", ['chunk' => $partPath]);
                    } else {
                        Log::error("[UPLOAD] Chunk faltando:", ['expected' => $partPath]);
                    }
                }
                fclose($outputFile);

                $filemanager = Filemanager::create([
                    'file_url' => 'streamit-laravel/' . $fileName,
                    'file_name' => $fileName,
                ]);

                Log::info('[UPLOAD] Arquivo final salvo e registrado no banco.', [
                    'file' => $fileName,
                    'final_path' => $finalFullPath,
                    'db_id' => $filemanager->id,
                ]);
            }

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            Log::error('[UPLOAD] Exceção capturada:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro interno. Veja os logs.'], 500);
        }
    }

    public function destroy(Request $request)
    {
        $url = $request->input('url');
        $activeDisk = env('ACTIVE_STORAGE', 'local');

        $parsedUrl = parse_url($url);
        $path = ltrim($parsedUrl['path'], '/');

        if ($activeDisk === 'local') {
            $path = str_replace('storage/', 'public/', $path);
        }

        $fileName = basename($path);

        if (public_path($path) && Storage::disk($activeDisk)->delete($path)) {
            $filemanager = Filemanager::where('file_name', $fileName)->first();
            if ($filemanager) {
                $filemanager->forceDelete();
            }
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 500);
    }

    public function SearchMedia(Request $request)
    {
        $query = $request->input('query');
        $mediaUrls = getMediaUrls($query);
        return response()->json(['mediaUrls' => $mediaUrls]);
    }
}