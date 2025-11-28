<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ValidateGithubToken
{
    /**
     * Validate the GitHub token and return the user data.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function handle(string $token): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'DevDashboard-App',
            ])->get('https://api.github.com/user');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'token' => __('Unable to connect to GitHub. Please check your internet connection.'),
            ]);
        }

        if ($response->status() === 200) {
            return $response->json();
        }

        if ($response->status() === 401) {
            throw ValidationException::withMessages([
                'token' => __('Invalid GitHub token. Please check your token and try again.'),
            ]);
        }

        if ($response->status() === 403) {
            throw ValidationException::withMessages([
                'token' => __('Token lacks required permissions or has been revoked.'),
            ]);
        }

        if ($response->status() === 429) {
            throw ValidationException::withMessages([
                'token' => __('GitHub API rate limit exceeded. Please try again later.'),
            ]);
        }

        throw ValidationException::withMessages([
            'token' => __('Failed to validate token. Please try again.'),
        ]);
    }
}
