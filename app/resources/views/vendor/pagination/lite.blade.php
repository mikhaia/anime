<nav class="pagination" role="navigation">
    <span class="pagination-prev">
        @if (request()->integer('page') <= 1)
            <span>&laquo; Предыдущая</span>
    @else
    <a href="?page={{ request()->integer('page') - 1 }}" rel="prev">&laquo; Предыдущая</a>
    @endif
    </span>

    <span class="pagination-info">
        Страница {{ request()->integer('page', 1) }}
    </span>

    <span class="pagination-next">
        <a href="?page={{ request()->integer('page', 1) + 1 }}" rel="next">Следующая &raquo;</a>
    </span>
</nav>