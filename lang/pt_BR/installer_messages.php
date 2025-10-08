<?php

return [

    /*
     * Traduções compartilhadas
     */
    'title' => 'Instalador Laravel',
    'next' => 'Próxima Etapa',
    'back' => 'Voltar',
    'finish' => 'Instalar',
    'forms' => [
        'errorTitle' => 'Ocorreram os seguintes erros:',
    ],

    /*
     * Página inicial
     */
    'welcome' => [
        'templateTitle' => 'Bem-vindo',
        'title'   => 'Instalador Laravel',
        'message' => 'Assistente de instalação e configuração fácil.',
        'next'    => 'Verificar Requisitos',
    ],

    /*
     * Requisitos do servidor
     */
    'requirements' => [
        'templateTitle' => 'Etapa 1 | Requisitos do Servidor',
        'title' => 'Requisitos do Servidor',
        'next'    => 'Verificar Permissões',
    ],

    /*
     * Permissões
     */
    'permissions' => [
        'templateTitle' => 'Etapa 2 | Permissões',
        'title' => 'Permissões',
        'next' => 'Configurar Ambiente',
    ],

    /*
     * Configuração do Ambiente
     */
    'environment' => [
        'menu' => [
            'templateTitle' => 'Etapa 3 | Configuração do Ambiente',
            'title' => 'Configurações do Ambiente',
            'desc' => 'Escolha como deseja configurar o arquivo <code>.env</code> da aplicação.',
            'wizard-button' => 'Assistente de Configuração',
            'classic-button' => 'Editor Clássico de Texto',
        ],
        'wizard' => [
            'templateTitle' => 'Etapa 3 | Configuração do Ambiente | Assistente',
            'title' => 'Assistente de <code>.env</code>',
            'tabs' => [
                'environment' => 'Ambiente',
                'database' => 'Banco de Dados',
                'application' => 'Aplicação',
            ],
            'form' => [
                'name_required' => 'O nome do ambiente é obrigatório.',
                'app_name_label' => 'Nome da Aplicação',
                'app_name_placeholder' => 'Nome da Aplicação',
                'app_environment_label' => 'Ambiente da Aplicação',
                'app_environment_label_local' => 'Local',
                'app_environment_label_developement' => 'Desenvolvimento',
                'app_environment_label_qa' => 'QA',
                'app_environment_label_production' => 'Produção',
                'app_environment_label_other' => 'Outro',
                'app_environment_placeholder_other' => 'Informe o ambiente...',
                'app_debug_label' => 'Debug da Aplicação',
                'app_debug_label_true' => 'Ativo',
                'app_debug_label_false' => 'Inativo',
                'app_log_level_label' => 'Nível de Log',
                'app_log_level_label_debug' => 'debug',
                'app_log_level_label_info' => 'info',
                'app_log_level_label_notice' => 'notice',
                'app_log_level_label_warning' => 'warning',
                'app_log_level_label_error' => 'error',
                'app_log_level_label_critical' => 'critical',
                'app_log_level_label_alert' => 'alert',
                'app_log_level_label_emergency' => 'emergency',
                'app_url_label' => 'URL da Aplicação',
                'app_url_placeholder' => 'URL da Aplicação',
                'db_connection_failed' => 'Não foi possível conectar ao banco de dados.',
                'db_connection_label' => 'Conexão com o Banco de Dados',
                'db_connection_label_mysql' => 'mysql',
                'db_connection_label_sqlite' => 'sqlite',
                'db_connection_label_pgsql' => 'pgsql',
                'db_connection_label_sqlsrv' => 'sqlsrv',
                'db_host_label' => 'Host do Banco de Dados',
                'db_host_placeholder' => 'Host do Banco de Dados',
                'db_port_label' => 'Porta do Banco de Dados',
                'db_port_placeholder' => 'Porta do Banco de Dados',
                'db_name_label' => 'Nome do Banco de Dados',
                'db_name_placeholder' => 'Nome do Banco de Dados',
                'db_username_label' => 'Usuário do Banco de Dados',
                'db_username_placeholder' => 'Usuário do Banco de Dados',
                'db_password_label' => 'Senha do Banco de Dados',
                'db_password_placeholder' => 'Senha do Banco de Dados',

                'app_tabs' => [
                    'more_info' => 'Mais Informações',
                    'broadcasting_title' => 'Broadcasting, Cache, Sessão &amp; Fila',
                    'broadcasting_label' => 'Driver de Broadcast',
                    'broadcasting_placeholder' => 'Driver de Broadcast',
                    'cache_label' => 'Driver de Cache',
                    'cache_placeholder' => 'Driver de Cache',
                    'session_label' => 'Driver de Sessão',
                    'session_placeholder' => 'Driver de Sessão',
                    'queue_label' => 'Driver de Fila',
                    'queue_placeholder' => 'Driver de Fila',
                    'redis_label' => 'Driver Redis',
                    'redis_host' => 'Host Redis',
                    'redis_password' => 'Senha Redis',
                    'redis_port' => 'Porta Redis',

                    'mail_label' => 'E-mail',
                    'mail_driver_label' => 'Driver de E-mail',
                    'mail_driver_placeholder' => 'Driver de E-mail',
                    'mail_host_label' => 'Host de E-mail',
                    'mail_host_placeholder' => 'Host de E-mail',
                    'mail_port_label' => 'Porta de E-mail',
                    'mail_port_placeholder' => 'Porta de E-mail',
                    'mail_username_label' => 'Usuário de E-mail',
                    'mail_username_placeholder' => 'Usuário de E-mail',
                    'mail_password_label' => 'Senha de E-mail',
                    'mail_password_placeholder' => 'Senha de E-mail',
                    'mail_encryption_label' => 'Criptografia de E-mail',
                    'mail_encryption_placeholder' => 'Criptografia de E-mail',

                    'pusher_label' => 'Pusher',
                    'pusher_app_id_label' => 'ID do App Pusher',
                    'pusher_app_id_palceholder' => 'ID do App Pusher',
                    'pusher_app_key_label' => 'Chave do App Pusher',
                    'pusher_app_key_palceholder' => 'Chave do App Pusher',
                    'pusher_app_secret_label' => 'Segredo do App Pusher',
                    'pusher_app_secret_palceholder' => 'Segredo do App Pusher',
                ],
                'buttons' => [
                    'setup_database' => 'Configurar Banco de Dados',
                    'setup_application' => 'Configurar Aplicação',
                    'install' => 'Instalar',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'Etapa 3 | Editor Clássico de Ambiente',
            'title' => 'Editor Clássico do .env',
            'save' => 'Salvar .env',
            'back' => 'Usar Assistente',
            'install' => 'Salvar e Instalar',
        ],
        'success' => 'As configurações do arquivo .env foram salvas.',
        'errors' => 'Não foi possível salvar o arquivo .env. Crie-o manualmente.',
    ],

    'install' => 'Instalar',

    /*
     * Log de instalação
     */
    'installed' => [
        'success_log_message' => 'Instalador Laravel instalado com sucesso em ',
    ],

    /*
     * Página Final
     */
    'final' => [
        'title' => 'Instalação Concluída',
        'templateTitle' => 'Instalação Concluída',
        'finished' => 'A aplicação foi instalada com sucesso.',
        'migration' => 'Saída do Console de Migração &amp; Seed:',
        'console' => 'Saída do Console da Aplicação:',
        'log' => 'Registro de Instalação:',
        'env' => 'Arquivo .env Final:',
        'exit' => 'Clique aqui para sair',
        'user_website' => 'Site do Usuário',
        'admin_panel' => 'Painel Administrativo',
    ],

    /*
     * Atualizador
     */
    'updater' => [
        'title' => 'Atualizador Laravel',

        'welcome' => [
            'title'   => 'Bem-vindo ao Atualizador',
            'message' => 'Bem-vindo ao assistente de atualização.',
        ],

        'overview' => [
            'title'   => 'Visão Geral',
            'message' => 'Há 1 atualização disponível.|Há :number atualizações disponíveis.',
            'install_updates' => 'Instalar Atualizações',
        ],

        'final' => [
            'title' => 'Concluído',
            'finished' => 'O banco de dados da aplicação foi atualizado com sucesso.',
            'exit' => 'Clique aqui para sair',
        ],

        'log' => [
            'success_message' => 'Atualização do Instalador Laravel realizada com sucesso em ',
        ],
    ],
];