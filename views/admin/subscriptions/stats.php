<section>
    <h1 class="mb-4">Statistiques abonnements</h1>
    <div class="row g-4 mb-4"><div class="col-lg-4"><div class="card shadow-sm"><div class="card-body"><h2 class="h6 text-uppercase text-muted">MRR</h2><p class="display-6 mb-0"><?= e((string) ($stats['mrr'] ?? 0)) ?> €</p></div></div></div><div class="col-lg-4"><div class="card shadow-sm"><div class="card-body"><h2 class="h6 text-uppercase text-muted">Churn</h2><p class="display-6 mb-0"><?= e((string) ($stats['churn_rate'] ?? 0)) ?> %</p></div></div></div></div>
    <canvas id="subscriptionsChart" height="120"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const dataset = <?= json_encode($stats['by_plan'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    new Chart(document.getElementById('subscriptionsChart'), { type: 'bar', data: { labels: dataset.map(item => item.name), datasets: [{ label: 'Abonnements', data: dataset.map(item => item.total) }] } });
    </script>
</section>