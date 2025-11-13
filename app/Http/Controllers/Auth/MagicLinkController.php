<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MagicLinkController extends Controller
{
    public function requestMagicLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // CORRECTION 1 : Ne pas mettre de 'password' car la colonne n'existe plus
        $user = User::firstOrCreate(
            ['email' => $request->email],
            [
                'name' => Str::before($request->email, '@'), // Ex: 'jean' pour 'jean@test.com'
                // 'password' => ... SUPPRIMÉ
            ]
        );

        // Création du lien en base
        $magicLink = MagicLink::create([
            'user_id' => $user->id,
            'token' => Str::random(60),
            'expires_at' => Carbon::now()->addMinutes(15), // [cite: 11]
        ]);

        // CORRECTION 2 : L'URL doit pointer vers le FRONTEND Angular (Port 4200)
        // Sinon l'utilisateur ne tombera jamais sur ta page VerifyComponent
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:4200');
        $url = "{$frontendUrl}/auth/verify/{$magicLink->token}";

        // Envoi via Queue [cite: 10]
        Mail::to($user->email)->queue(new MagicLinkMail($url));

        return response()->json(['message' => 'Magic link sent successfully.']);
    }

    public function verifyMagicLink($token)
    {
        // Vérification validité et expiration [cite: 11]
        $magicLink = MagicLink::where('token', $token)
                        ->where('expires_at', '>', Carbon::now())
                        ->first();

        if (!$magicLink) {
            return response()->json(['message' => 'Invalid or expired magic link.'], 401);
        }

        $user = $magicLink->user;

        // Nettoyage : le lien est à usage unique
        $magicLink->delete();

        // Création du token Sanctum [cite: 13]
        $authToken = $user->createToken('auth-token')->plainTextToken;

        // CORRECTION 3 : Renvoyer l'user et ses rôles
        // Le frontend en a besoin pour savoir "qui suis-je" tout de suite
        return response()->json([
            'token' => $authToken,
            'user' => $user->load('roles.permissions') 
        ]);
    }
}