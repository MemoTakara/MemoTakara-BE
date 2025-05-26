<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAPI extends Controller
{
    // login, register callback
    public function callback()
    {
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $frontendAuthCallbackPath = '/google'; // Your React route for handling the token
        $frontendAuthErrorPath = '/auth/error';   // Your React route for showing generic errors

        try {
            // Use stateless() because this route might be in api.php (no session by default).
            // This tells Socialite not to expect or verify a 'state' from Laravel's session.
            // Google's use of PKCE ensures security for this flow.
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find user by google_id
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                // User exists, log them in (by generating token for React)
                Log::info('Google Login: User found by google_id.', ['email' => $user->email, 'id' => $user->id]);
            } else {
                // User not found by google_id, check by email to link accounts
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    // User exists with this email, but google_id was not set.
                    // Update user with google_id and avatar.
                    $user->update([
                        'google_id' => $googleUser->getId(),
                    ]);
                    Log::info('Google Login: User found by email, linked google_id.', ['email' => $user->email, 'id' => $user->id]);
                } else {
                    $emailParts = explode('@', $googleUser->getEmail());
                    $suggestedUsername = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $emailParts[0])); // Sanitize

                    // Ensure username is unique (simple example, you might need a loop or a more robust strategy)
                    $username = $googleUser->username ?? $suggestedUsername;

                    if (empty($username)) {
                        $counter = 1;
                        while (User::where('username', $suggestedUsername)->exists()) {
                            $username = $suggestedUsername . $counter;
                            $counter++;
                        }
                    }

                    // User does not exist by google_id or email, create a new user (Register)
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'username' => $username,
                        'password' => null, // Or Hash::make(Str::random(24)) if a password must be set for some reason
                        'email_verified_at' => now(), // Assume email is verified by Google
                    ]);
                    Log::info('Google Register: New user created.', ['email' => $user->email, 'id' => $user->id]);
                }
            }

            // Nếu user chưa được xác minh email, cập nhật email_verified_at
            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
                $user->save();
            }

            // Create a Sanctum API token for the user
            $apiToken = $user->createToken('google-auth-token-' . Str::random(5), ['role:user'])->plainTextToken; // Added abilities example

            // Redirect the user's browser to the React frontend callback route with the token
            $redirectUrl = "{$frontendUrl}{$frontendAuthCallbackPath}?token={$apiToken}";
            Log::info('Redirecting to frontend with token.', ['url' => $redirectUrl, 'user_id' => $user->id]);
            return redirect()->to($redirectUrl);

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            // This error specifically means there was an issue with the 'state' parameter.
            // With ->stateless(), this should be less common unless 'state' was manually passed and is incorrect.
            Log::warning('Google OAuth Callback Invalid State: ' . $e->getMessage(), ['exception_trace' => $e->getTraceAsString()]);
            return redirect()->to("{$frontendUrl}{$frontendAuthErrorPath}?message=InvalidOAuthState&code=419");
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Guzzle exceptions usually indicate problems with the HTTP request to Google's token endpoint
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
            Log::error('Google OAuth Guzzle ClientException: ' . $e->getMessage() . ' | Response: ' . $responseBody, ['exception_trace' => $e->getTraceAsString()]);
            return redirect()->to("{$frontendUrl}{$frontendAuthErrorPath}?message=OAuthCommunicationError&code=" . $e->getCode());
        } catch (Exception $e) {
            Log::error('Google OAuth Callback General Error: ' . $e->getMessage(), ['exception_class' => get_class($e), 'exception_trace' => $e->getTraceAsString()]);
            return redirect()->to("{$frontendUrl}{$frontendAuthErrorPath}?message=GoogleLoginFailed&code=500");
        }
    }

    // redirect
    public function redirect()
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['redirect_url' => $redirectUrl]);
    }

    // unlink
    public function unlinkGoogle()
    {
        $user = auth()->user();

        if (is_null($user->google_id)) {
            return response()->json(['message' => 'Google account already unlinked.'], 400);
        }

        $user->google_id = null;
        $user->email_verified_at = null;
        $user->save();

        return response()->json(['message' => 'Google account unlinked successfully.']);
    }
}
