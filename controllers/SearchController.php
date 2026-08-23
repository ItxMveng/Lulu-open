<?php
declare(strict_types=1);

final class SearchController extends Controller
{
    private Profile $profiles;
    private Offer $offers;
    private SavedSearch $savedSearches;
    private MatchingEngine $matching;

    public function __construct()
    {
        $this->profiles = new Profile();
        $this->offers = new Offer();
        $this->savedSearches = new SavedSearch();
        $this->matching = new MatchingEngine();
    }

    public function searchAll(): void
    {
        $filters = $this->collectFilters();
        // Onglet par défaut selon le rôle : un candidat cherche d'abord des OFFRES,
        // un recruteur cherche d'abord des TALENTS. L'utilisateur peut basculer.
        $defaultTab = current_role() === 'client' ? 'offres' : 'profils';
        $activeTab = (string) ($_GET['tab'] ?? $defaultTab);
        $profiles = $this->profiles->search($filters);
        $offers = $this->offers->publicSearch($filters);

        if (!empty($filters['q'])) {
            $profiles['items'] = $this->matching->rankResults($profiles['items'], (string) $filters['q']);
        }

        $this->render('pages/search', [
            'title' => 'Recherche',
            'filters' => $filters,
            'profiles' => $profiles,
            'offers' => $offers,
            'activeTab' => $activeTab,
            'categoriesList' => (new Category())->all(),
            'countriesList' => Reference::countries(),
            'savedSearches' => is_auth() ? $this->savedSearches->allForUser((int) current_user_id()) : [],
        ]);
    }

    public function searchProfiles(): void
    {
        $_GET['tab'] = 'profils';
        $this->searchAll();
    }

    public function searchOffers(): void
    {
        $_GET['tab'] = 'offres';
        $this->searchAll();
    }

    private function collectFilters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'category' => trim((string) ($_GET['category'] ?? '')),
            'country' => trim((string) ($_GET['country'] ?? '')),
            'location' => trim((string) ($_GET['location'] ?? '')),
            'radius' => trim((string) ($_GET['radius'] ?? '')),
            'rate_min' => trim((string) ($_GET['rate_min'] ?? '')),
            'rate_max' => trim((string) ($_GET['rate_max'] ?? '')),
            'available' => !empty($_GET['available']),
            'sort' => trim((string) ($_GET['sort'] ?? 'pertinence')),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'per_page' => max(1, (int) ($_GET['per_page'] ?? 12)),
        ];
    }
}