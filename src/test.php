<?php


public function callback()
{
    $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
    $frontendAuthCallbackPath = '/google';
    $frontendAuthErrorPath = '/auth/error';

    try {
        $googleUser = Socialite::driver('google')->stateless()->user();
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            Log::info('Google Login: User found by google_id.', ['email' => $user->email, 'id' => $user->id]);
        } else {
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update(['google_id' => $googleUser->getId()]);
                Log::info('Google Login: User found by email, linked google_id.', ['email' => $user->email, 'id' => $user->id]);
            } else {
                $emailParts = explode('@', $googleUser->getEmail());
                $suggestedUsername = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $emailParts[0]));

                // Kiểm tra nếu người dùng đã có username
                $username = $googleUser->username ?? $suggestedUsername;

                if (empty($username)) {
                    $counter = 1;
                    while (User::where('username', $suggestedUsername)->exists()) {
                        $username = $suggestedUsername . $counter;
                        $counter++;
                    }
                }

                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'username' => $username,
                    'password' => null,
                    'email_verified_at' => now(),
                ]);
                Log::info('Google Register: New user created.', ['email' => $user->email, 'id' => $user->id]);
            }
        }

        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }

        $apiToken = $user->createToken('google-auth-token-' . Str::random(5), ['role:user'])->plainTextToken;

        $redirectUrl = "{$frontendUrl}{$frontendAuthCallbackPath}?token={$apiToken}";
        Log::info('Redirecting to frontend with token.', ['url' => $redirectUrl, 'user_id' => $user->id]);
        return redirect()->to($redirectUrl);

    } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
        Log::warning('Google OAuth Callback Invalid State: ' . $e->getMessage(), ['exception_trace' => $e->getTraceAsString()]);
        return redirect()->to("{$frontendUrl}{$frontendAuthErrorPath}?message=InvalidOAuthState&code=419");
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
        Log::error('Google OAuth Guzzle ClientException: ' . $e->getMessage() . ' | Response: ' . $responseBody, ['exception_trace' => $e->getTraceAsString()]);
        return redirect()->to("{$frontendUrl}{$frontendAuthErrorPath}?message=OAuthCommunicationError&code=" . $e->getCode());
    } catch (Exception $e) {
        Log::error('Google OAuth Callback General Error: ' . $e->getMessage(), ['exception_class' => get_class($e), 'exception_trace' => $e->getTraceAsString()]);
        return redirect()->to("{$frontendUrl}{$frontendAuthErrorPath}?message=GoogleLoginFailed&code=500");
    }
}










