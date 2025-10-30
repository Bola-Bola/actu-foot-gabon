<?php

namespace App\Http\Controllers;

use App\Models\Actualite;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActualiteController extends Controller
{
    /**
     * Afficher la page principale avec formulaire et liste
     */
    public function index()
    {
        $actualites = Actualite::with('admin')
            ->orderBy('date_publication', 'desc')
            ->get();

        $admins = Admin::all();

        // Statistiques
        $totalArticles = $actualites->count();
        $publishedArticles = $actualites->where('publie', true)->count();
        $draftArticles = $actualites->where('publie', false)->count();
        $totalCategories = $actualites->pluck('categorie')->unique()->count();

        return view('actualites', compact(
            'actualites',
            'admins',
            'totalArticles',
            'publishedArticles',
            'draftArticles',
            'totalCategories'
        ));
    }

    /**
     * Créer une nouvelle actualité
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,avi,mov,webm|max:20480',
            'categorie' => 'required|string',
            'date_publication' => 'required|date',
            'publie' => 'nullable',
        ]);

        // Gérer l'upload du média
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $filename = time() . '_' . $file->getClientOriginalName();
            $mediaPath = $file->storeAs('actualites', $filename, 'public');
            $validated['image'] = $mediaPath;
        }

        // Convertir la checkbox en boolean
        $validated['publie'] = $request->has('publie') ? 1 : 0;

        $actualite = Actualite::create($validated);

        // Support AJAX
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Article créé avec succès!',
                'data' => $actualite
            ], 201);
        }

        return redirect()->route('actualites.index')
            ->with('success', 'Article créé avec succès !');
    }

    /**
     * Afficher une actualité spécifique
     */
    public function show(Actualite $actualite)
    {
        // Support AJAX
        if (request()->wantsJson() || request()->ajax()) {
            $actualite->load('admin');
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $actualite->id,
                    'admin_id' => $actualite->admin_id,
                    'titre' => $actualite->titre,
                    'contenu' => $actualite->contenu,
                    'image' => $actualite->image ? asset('storage/' . $actualite->image) : null,
                    'categorie' => $actualite->categorie,
                    'date_publication' => $actualite->date_publication->format('Y-m-d\TH:i'),
                    'publie' => $actualite->publie,
                    'admin_nom' => $actualite->admin->nom ?? 'Inconnu'
                ]
            ]);
        }

        $actualites = Actualite::with('admin')
            ->orderBy('date_publication', 'desc')
            ->get();

        $admins = Admin::all();

        // Statistiques
        $totalArticles = $actualites->count();
        $publishedArticles = $actualites->where('publie', true)->count();
        $draftArticles = $actualites->where('publie', false)->count();
        $totalCategories = $actualites->pluck('categorie')->unique()->count();

        $actualite->load('admin');

        return view('actualites', compact(
            'actualites',
            'actualite',
            'admins',
            'totalArticles',
            'publishedArticles',
            'draftArticles',
            'totalCategories'
        ));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Actualite $actualite)
    {
        // Support AJAX - CORRECTION IMPORTANTE ICI
        if (request()->wantsJson() || request()->ajax()) {
            $actualite->load('admin');
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $actualite->id,
                    'admin_id' => $actualite->admin_id,
                    'titre' => $actualite->titre,
                    'contenu' => $actualite->contenu,
                    'image' => $actualite->image ? asset('storage/' . $actualite->image) : null,
                    'categorie' => $actualite->categorie,
                    'date_publication' => $actualite->date_publication->format('Y-m-d\TH:i'),
                    'publie' => $actualite->publie,
                    'is_video' => $actualite->isVideo()
                ]
            ]);
        }

        $actualites = Actualite::with('admin')
            ->orderBy('date_publication', 'desc')
            ->get();

        $admins = Admin::all();

        // Statistiques
        $totalArticles = $actualites->count();
        $publishedArticles = $actualites->where('publie', true)->count();
        $draftArticles = $actualites->where('publie', false)->count();
        $totalCategories = $actualites->pluck('categorie')->unique()->count();

        return view('actualites', compact(
            'actualites',
            'actualite',
            'admins',
            'totalArticles',
            'publishedArticles',
            'draftArticles',
            'totalCategories'
        ));
    }

    /**
     * Mettre à jour une actualité
     */
    public function update(Request $request, Actualite $actualite)
    {
        $validated = $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,avi,mov,webm|max:20480',
            'categorie' => 'required|string',
            'date_publication' => 'required|date',
            'publie' => 'nullable',
        ]);

        // Gérer le nouveau média
        if ($request->hasFile('media')) {
            if ($actualite->image && Storage::disk('public')->exists($actualite->image)) {
                Storage::disk('public')->delete($actualite->image);
            }

            $file = $request->file('media');
            $filename = time() . '_' . $file->getClientOriginalName();
            $mediaPath = $file->storeAs('actualites', $filename, 'public');
            $validated['image'] = $mediaPath;
        }

        $validated['publie'] = $request->has('publie') ? 1 : 0;

        $actualite->update($validated);

        // Support AJAX
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Article modifié avec succès!',
                'data' => $actualite
            ]);
        }

        return redirect()->route('actualites.index')
            ->with('success', 'Article modifié avec succès !');
    }

    /**
     * Supprimer une actualité
     */
    public function destroy(Actualite $actualite)
    {
        try {
            if ($actualite->image && Storage::disk('public')->exists($actualite->image)) {
                Storage::disk('public')->delete($actualite->image);
            }

            $actualite->delete();

            // Support AJAX
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Article supprimé avec succès!'
                ]);
            }

            return redirect()->route('actualites.index')
                ->with('success', 'Article supprimé avec succès !');
        } catch (\Exception $e) {
            // Support AJAX
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('actualites.index')
                ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}

