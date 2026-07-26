<?php
declare(strict_types=1);

final class DashboardController extends Controller
{
    public function redirectDashboard(): never
    {
        AuthMiddleware::requireAuth();
        redirect(dashboard_path_for_role(current_role()));
    }

    public function client(): void
    {
        $uid = (int) current_user_id();
        $applications = (new Application())->sentByApplicant($uid);
        $savedSearches = (new SavedSearch())->allForUser($uid);
        $profile = (new Profile())->getByUserId($uid);

        $this->render('client/dashboard', [
            'title' => 'Tableau de bord',
            'user' => auth_user(),
            'hasProfile' => !empty($profile),
            'stats' => [
                'applications' => count($applications),
                'saved_searches' => count($savedSearches),
                'unread_messages' => $this->unreadMessages($uid),
                'notifications' => count((new Notification())->getUnread($uid)),
            ],
            'recentApplications' => array_slice($applications, 0, 5),
        ]);
    }

    public function entreprise(): void
    {
        $uid = (int) current_user_id();
        $offers = (new Offer())->allByEntreprise($uid);
        $received = (new Application())->receivedByEntreprise($uid);
        $activeOffers = array_filter($offers, static fn (array $o): bool => ($o['status'] ?? '') === 'active');
        $pending = array_filter($received, static fn (array $a): bool => ($a['status'] ?? '') === 'en_attente');

        $this->render('entreprise/dashboard', [
            'title' => 'Tableau de bord',
            'user' => auth_user(),
            'stats' => [
                'active_offers' => count($activeOffers),
                'applications' => count($received),
                'pending' => count($pending),
                'unread_messages' => $this->unreadMessages($uid),
            ],
            'recentApplications' => array_slice($received, 0, 5),
            'recentOffers' => array_slice($offers, 0, 5),
        ]);
    }

    private function unreadMessages(int $userId): int
    {
        $conversations = (new Message())->getConversations($userId);
        return array_sum(array_map(static fn (array $c): int => (int) ($c['unread_count'] ?? 0), $conversations));
    }

    public function admin(): void
    {
        $this->render('admin/dashboard', ['title' => 'Tableau de bord admin'], 'admin');
    }
}