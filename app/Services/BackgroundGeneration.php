<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * Runs slow AI work (petitions, form import) after the response is sent, so the
 * hosting's 60-second limit never cuts the request. The browser asks for the status
 * every few seconds; a generated Word file waits in a private folder only until it
 * is downloaded (or for one hour at most) and is then deleted.
 */
class BackgroundGeneration
{
    private const TTL_SECONDS = 3600;

    /**
     * Registers the work and runs it right after the response reaches the browser
     * (when the application terminates, after litespeed/fastcgi_finish_request).
     *
     * @param  callable(string): array<string, mixed>  $work  receives the token; returns extra data for the status (e.g. a redirect)
     */
    public function start(int $userId, callable $work): string
    {
        $this->deleteOldFiles();

        $token = Str::random(40);
        Cache::put($this->key($token), ['user_id' => $userId, 'status' => 'pending'], self::TTL_SECONDS);

        $started = false;

        app()->terminating(function () use ($token, $work, &$started): void {
            if ($started) {
                return;
            }

            $started = true;
            ignore_user_abort(true);
            set_time_limit(0);

            try {
                $extra = $work($token);
                $this->update($token, ['status' => 'done', ...$extra]);
            } catch (Throwable $e) {
                report($e);
                $this->update($token, [
                    'status' => 'failed',
                    'message' => $e instanceof GenerationFailed ? $e->getMessage() : 'Não foi possível concluir agora. Tente novamente em instantes.',
                ]);
            }
        });

        return $token;
    }

    /**
     * Keeps a generated Word file until it is downloaded.
     */
    public function storeFile(string $token, string $temporaryPath, string $fileName): void
    {
        File::ensureDirectoryExists($this->directory());
        File::move($temporaryPath, $this->filePath($token));
        $this->update($token, ['file_name' => $fileName]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function status(string $token, int $userId): ?array
    {
        $status = Cache::get($this->key($token));

        return is_array($status) && ($status['user_id'] ?? null) === $userId ? $status : null;
    }

    public function filePath(string $token): string
    {
        return $this->directory().'/'.$token.'.docx';
    }

    /**
     * Ends a generation once its file is being downloaded (the download deletes the file).
     */
    public function forget(string $token): void
    {
        Cache::forget($this->key($token));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function update(string $token, array $data): void
    {
        $current = Cache::get($this->key($token), []);
        Cache::put($this->key($token), [...$current, ...$data], self::TTL_SECONDS);
    }

    private function deleteOldFiles(): void
    {
        foreach (File::glob($this->directory().'/*.docx') as $file) {
            if (File::lastModified($file) < time() - self::TTL_SECONDS) {
                File::delete($file);
            }
        }
    }

    private function directory(): string
    {
        return storage_path('app/private/geracoes');
    }

    private function key(string $token): string
    {
        return 'geracao:'.$token;
    }
}
