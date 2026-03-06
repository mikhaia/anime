<nav class="pagination" role="navigation">
    <span class="pagination-prev">
        @if (request()->integer('page') <= 1)
            <b>&laquo; <span class="hide-mobile">Предыдущая</span></b>
        @else
            <a href="?page={{ request()->integer('page') - 1 }}" rel="prev">&laquo; Предыдущая</a>
        @endif
    </span>

    <b class="pagination-info">
        <span class="hide-mobile">Страница</span> {{ request()->integer('page', 1) }}
    </b>

    <b class="pagination-next">
        <a href="?page={{ request()->integer('page', 1) + 1 }}" rel="next">
            <span class="hide-mobile">Следующая</span> &raquo;
        </a>
    </b>
</nav>
