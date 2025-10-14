# Webhook de Processamento de Vídeos

Sistema simples que envia automaticamente episódios e entertainments criados para um serviço de processamento de vídeos via webhook.

## 🎯 O que faz?

Quando um **Episode** ou **Entertainment** é criado, o sistema automaticamente:

1. Extrai a URL do vídeo (de `video_url_input` ou `StreamContentMappings`)
2. Envia um POST para o webhook configurado com os dados do vídeo

## 🔧 Configuração

### 1. Variáveis de Ambiente

Adicione ao seu `.env`:

```env
VIDEO_PROCESSING_WEBHOOK_URL=https://embeddingplatclaudio-production.up.railway.app/api/v1/videos/process
VIDEO_PROCESSING_BEARER_TOKEN=development_key
VIDEO_PROCESSING_AUTO_SEND=true
```

**Configurações:**
- `VIDEO_PROCESSING_WEBHOOK_URL`: URL do webhook de processamento
- `VIDEO_PROCESSING_BEARER_TOKEN`: Token de autenticação Bearer
- `VIDEO_PROCESSING_AUTO_SEND`: `true` para enviar automaticamente, `false` para desativar

### 2. Queue Worker

Como os listeners são assíncronos (implements `ShouldQueue`), você precisa rodar o worker:

```bash
php artisan queue:work
```

## 📤 Payload Enviado

### Para Entertainment

```json
{
  "entertainment_id": 122,
  "video_url": "https://b-vz-f6acd24e-065.tv.pandavideo.com.br/13813e5e-ca78-4cc4-927b-8695fced5234/playlist.m3u8"
}
```

### Para Episode

```json
{
  "episode_id": 45,
  "video_url": "https://b-vz-f6acd24e-065.tv.pandavideo.com.br/1fbcb02a-0738-4491-a7ab-04158641dd98/playlist.m3u8"
}
```

## 🔒 Headers Enviados

```
Accept: application/json
Authorization: Bearer development_key
Content-Type: application/json
```

## 🚀 Como Funciona

### Fluxo Automático

1. **Entertainment/Episode criado** (via admin ou API)
2. **Event disparado** (`EntertainmentCreated` ou `EpisodeCreated`)
3. **Listener executado** (em background via queue)
4. **URL extraída** do model
5. **Webhook chamado** com os dados
6. **Log gerado** (sucesso ou erro)

## 📁 Arquivos Criados

```
app/
├── Events/
│   ├── EntertainmentCreated.php
│   └── EpisodeCreated.php
├── Listeners/
│   ├── SendEntertainmentToProcessing.php
│   └── SendEpisodeToProcessing.php
└── Services/
    └── VideoProcessingWebhookService.php

Modules/
├── Entertainment/Models/Entertainment.php (modificado)
└── Episode/Models/Episode.php (modificado)

app/Providers/EventServiceProvider.php (modificado)
config/services.php (modificado)
.env.example (modificado)
```

## 🔍 Logs

Para ver os logs de envio:

```bash
tail -f storage/logs/laravel.log | grep VideoProcessingWebhook
```

### Exemplos de Log

**Sucesso:**
```
[2025-10-14 16:00:00] local.INFO: VideoProcessingWebhook: Successfully sent {"type":"entertainment","id":122,"status":200}
```

**Erro:**
```
[2025-10-14 16:00:00] local.ERROR: VideoProcessingWebhook: Failed to send {"type":"episode","id":45,"status":500}
```

## 🛠️ Desativar Envio Automático

Se quiser desativar temporariamente o envio automático:

**Opção 1: Via .env**
```env
VIDEO_PROCESSING_AUTO_SEND=false
```

**Opção 2: Via código (temporário)**

Edite o model e comente a linha:
```php
static::created(function ($episode) {
    // if (config('services.video_processing.auto_send', true)) {
    //     event(new \App\Events\EpisodeCreated($episode));
    // }
});
```

## ⚠️ Validações

O sistema NÃO envia webhook se:
- URL do webhook não estiver configurada
- Bearer token não estiver configurado
- Vídeo não tiver URL (`video_url_input` ou stream mapping vazio)

Nesses casos, apenas um log de warning é gerado, sem erros.

## 🔄 Retry

Os listeners são configurados com queue, então se o webhook falhar:
- Laravel tentará novamente automaticamente (3 vezes por padrão)
- Use `php artisan queue:failed` para ver jobs que falharam
- Use `php artisan queue:retry {id}` para retentar manualmente

## 📊 Verificar Status da Queue

```bash
# Ver failed jobs
php artisan queue:failed

# Retentar todos os failed
php artisan queue:retry all

# Limpar failed jobs
php artisan queue:flush
```

## 🧪 Testar Manualmente

Para testar sem criar um registro:

```php
// No Tinker (php artisan tinker)

$episode = \Modules\Episode\Models\Episode::find(1);
$service = new \App\Services\VideoProcessingWebhookService();
$service->sendVideoForProcessing('episode', $episode->id, 'https://test.com/video.m3u8');
```

## 🔑 Detalhes Técnicos

### Extração de URL

O sistema busca a URL do vídeo na seguinte ordem:

1. **`video_url_input`** (campo direto no model)
2. **EpisodeStreamContentMapping** (primeiro registro)
3. **entertainmentStreamContentMappings** (primeiro registro)

Se nenhum for encontrado, o webhook não é enviado.

### Timeout

- **Timeout do HTTP**: 30 segundos
- **Sem retry automático** (Laravel queue cuida disso)

## 📝 Exemplo Completo

```bash
# 1. Configurar .env
echo "VIDEO_PROCESSING_WEBHOOK_URL=https://embeddingplatclaudio-production.up.railway.app/api/v1/videos/process" >> .env
echo "VIDEO_PROCESSING_BEARER_TOKEN=development_key" >> .env
echo "VIDEO_PROCESSING_AUTO_SEND=true" >> .env

# 2. Iniciar worker
php artisan queue:work &

# 3. Criar um episode (via admin ou API)
# O webhook será enviado automaticamente!

# 4. Verificar logs
tail -f storage/logs/laravel.log | grep VideoProcessingWebhook
```

## 🎬 Pronto!

Agora toda vez que um Entertainment ou Episode for criado com uma URL de vídeo, o webhook será chamado automaticamente!

---

**Desenvolvido para integração com o serviço de processamento de vídeos**
