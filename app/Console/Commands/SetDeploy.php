<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetDeploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set-deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Сборка проекта и отправка изменений на Git';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gitUrl = env('URL_GIT');
        $deploySecret = env('DEPLOY_SECRET');
        
        // Выполнение сборки npm
        $this->info('Запуск сборки npm...');
        exec('npm run build', $output, $status);
        if ($status !== 0) {
            $this->error('Ошибка сборки npm');
            $this->error(implode("\n", $output));
            return Command::FAILURE;
        }
        $this->info('Сборка npm выполнена успешно');
        
        // Отправка изменений в git
        $this->info('Отправка изменений в Git...');
        $gitCommand = 'git add . && git commit -m "Deploy update" && git push ' . ($gitUrl ?: 'origin master');
        exec($gitCommand, $output, $status);
        if ($status !== 0) {
            $this->error('Ошибка отправки на git');
            $this->error(implode("\n", $output));
            return Command::FAILURE;
        }
        
        $this->info('Сборка и отправка на Git выполнены успешно');
        return Command::SUCCESS;
    }
}
