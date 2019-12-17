import $ from 'jquery'

$(document).ready(function () {

    $('#panel #ratedSwitch').on('change', function () {
        location.replace($(this).is(':checked') ? $(this).data('onLink') : $(this).data('offLink'));
    });

    $('#panel select[name=rank]').on('change', function () {

        console.debug('Rank: ' + $(this).val());
        const rank = $(this);
        const info = $('#panel select[name=info][data-id=' + rank.data('id') + ']');

        if (rank.val() === '1') {
            info.val('ok');
        } else if (rank.val() !== '-' && info.val() === 'ok') {
            info.val('-');
        }

        info.change();
    });

    $('#panel select[name=info]').on('change', function () {
        console.debug('Info: ' + $(this).val());
        const info = $(this);
        const rank = $('#panel select[name=rank][data-id=' + info.data('id') + ']');

        if (info.val() !== '-' && rank.val() !== '-') {
            $.ajax({
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                url: '/panel/' + info.data('id'),
                type: 'PATCH',
                data: JSON.stringify({rank: parseFloat(rank.val()), info: info.val()}),
                success: function (response, textStatus, jqXhr) {
                    info.removeClass('bg-danger').addClass('bg-success');
                    rank.removeClass('bg-danger').addClass('bg-success');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    info.removeClass('bg-success').addClass('bg-danger');
                    rank.removeClass('bg-success').addClass('bg-danger');
                    console.error("The following error occured: " + textStatus, errorThrown);
                }
            });
        }
    });

});
