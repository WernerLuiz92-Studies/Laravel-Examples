<?php

/**
 * Este comando eu fiz para importar alguns dados de arquivos .json e atualizar
 * algumas cobranças que estavam com problemas no banco de dados.
 * 
 * Está aqui pois é um bom exemplo de como utilizar o comando ProgressBar. 
 * 
 * Também possui um exemplo de como utilizar o comando map e reject em uma collection linhas 59 a 63.
 */

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Facade\Ignition\DumpRecorder\Dump;
use Illuminate\Support\Facades\Storage;

class InvoicesUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        system('clear');

        $this->info('Coletando dados das cobranças através dos arquivos Json');
        $this->output->newLine();

        $files = collect(Storage::allFiles('json/'))->map(function ($file) {
            return str_starts_with($file, 'json/000') ? $file : false;
        })->reject(function ($file) {
            return $file === false;
        });

        if ($files->isEmpty()) {
            $this->info('No files found');
            return 0;
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();
        $this->output->newLine();

        $files->each(function ($file) use ($bar) {
            $codbill = str_replace("json/", '', $file);
            $codbill = str_replace(".json", '', $codbill);

            $invoice = Invoice::where('codbill', $codbill)->first();

            $json = Storage::get($file);

            $json = json_decode($json);
            $newJson = json_encode($json);

            $this->output->newLine();
            $this->info('-------------------------------------');
            $this->info('File: '.$file);
            /**
             * Dados sensíveis removidos
             */
            $this->info('------- Atualizando Cobrança --------');
            $invoice->situation = 'c';
            $invoice->urlbill = null;
            $invoice->json = $newJson;
            $invoice->save();
            /**
             * Dados sensíveis removidos
             */
            $this->info('------ Atualização Finalizada -------');
            $this->output->newLine();


            sleep(1);
            $bar->advance();
            $this->output->newLine();
        });

        $bar->finish();
        $this->output->newLine();

        return 0;
    }
}
