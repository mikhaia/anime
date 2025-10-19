@if ($paginator->hasPages())
<nav class="pagination" role="navigation" aria-label="Pagination">
    <span class="pagination-prev">
        @if ($paginator->onFirstPage())
        <span aria-disabled="true">&laquo; Предыдущая</span>
        @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Предыдущая</a>
        @endif
    </span>

    <span class="pagination-info">
        Страница {{ $paginator->currentPage() }}
    </span>

    <span class="pagination-next">
        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next">Следующая &raquo;</a>
        @else
        <span aria-disabled="true">Следующая &raquo;</span>
        @endif
    </span>
</nav>
@endif