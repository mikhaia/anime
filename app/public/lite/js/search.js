const $suggestionsList = $('#search-suggestions');

$('#search-input').on('input', function() {
    const query = $(this).val().trim();

    if (!query.length) {
        $suggestionsList.hide().empty();
        return;
    }

    $.get(`/api/search-suggestions?query=${encodeURIComponent(query)}`, function(data) {
        if (!data.length) {
            $suggestionsList.hide().empty();
            return;
        }

        const html = data.map(anime => `
            <li>
                <a href="/anime/${anime.id}">
                    ${anime.title}
                </a>
            </li>
        `).join('');

        $suggestionsList.html(html).show();
    });
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('.search-form').length) {
        $suggestionsList.hide();
    }
});
