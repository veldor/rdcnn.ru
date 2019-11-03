$(function () {
    // покажу всплывающие подсказки
    $('#loginHint').popover({
        html: true,
        'placement' : 'auto',
        trigger: 'hover',
        content: function () {
            return '<img alt="Подсказка для логина" src="/images/loginHint.png" />';
        }
    });
});