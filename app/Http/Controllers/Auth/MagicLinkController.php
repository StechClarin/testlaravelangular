<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use App\Models\Role; // Import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MagicLinkController extends Controller
{
    public function requestMagicLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // 1. Trouver ou Créer l'utilisateur (géré par 'email' unique)
        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => Str::before($request->email, '@')]
        );

        // --- CORRECTION DE LA LOGIQUE D'ATTRIBUTION DE RÔLE ---
        // On vérifie si l'utilisateur a DÉJÀ des rôles.
        // S'il n'en a aucun (count == 0), on lui donne le rôle 'user'
        if ($user->roles()->count() == 0) {
            $userRole = Role::where('slug', 'user')->first();
            if ($userRole) {
                $user->roles()->attach($userRole);
            }
        }
        // (S'il a déjà un rôle, comme 'admin', on ne touche à rien)
        // ----------------------------------------------------

        // 3. Invalider les anciens liens (Sécurité)
        MagicLink::where('user_id', $user->id)->delete();

        // 4. Créer le nouveau lien
        $magicLink = MagicLink::create([
            'user_id' => $user->id,
            'token' => Str::random(60),
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        // 5. Générer l'URL Frontend
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:4200');
        $url = "{$frontendUrl}/auth/verify/{$magicLink->token}";

        // 6. Envoyer l'email
        Mail::to($user->email)->queue(new MagicLinkMail($url));

        // 7. Réponse JSON (pour le debug)
        return response()->json([
            'message' => 'Magic link sent successfully.',
            'url' => $url 
        ]);
    }

    public function verifyMagicLink($token)
    {
        // ... (Ta fonction verifyMagicLink est déjà parfaite et sécurisée) ...
        // 1. Nettoyage des tokens expirés
        MagicLink::where('expires_at', '<', Carbon::now())->delete();

        // 2. Vérification du token
        $magicLink = MagicLink::where('token', $token)
                        ->where('expires_at', '>', Carbon::now())
                        ->first();

        if (!$magicLink) {
            return response()->json(['message' => 'Lien invalide ou expiré.'], 401);
        }

        $user = $magicLink->user;

        // 3. Suppression du token (usage unique)
        $magicLink->delete();

        // 4. Création du token d'API
        $authToken = $user->createToken('auth-token')->plainTextToken;

        // 5. Réponse
        return response()->json([
            'token' => $authToken,
            'user' => $user->load('roles.permissions') 
        ]);
    }
}