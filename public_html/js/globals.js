function serialize(obj) {
    const str = [];
    for (let p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
}

function createModal(header, text, normalReset) {
    let modal = $('<div id="myModal" class="modal fade mode-choose"><div class="modal-dialog  modal-lg"><div class="modal-content"><div class="modal-header">' + header + '</div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-danger"  data-dismiss="modal" type="button" id="cancelActionButton">Отмена</button></div></div></div>');
    $('body').append(modal);
    if (!normalReset)
        dangerReload();
    modal.modal({
        keyboard: true,
        show: true
    });
    modal.on('hidden.bs.modal', function () {
        normalReload();
        modal.remove();
        $('div.wrap div.container, div.wrap nav').removeClass('blured');
    });
    $('div.wrap div.container, div.wrap nav').addClass('blured');
    return modal;
}

// Функция вызова пустого модального окна
function makeModal(header, text, delayed, normalReset, delay) {
    if (!text)
        text = '';
    if (delay) {
        setTimeout(function () {
                return createModal(header, text, normalReset);
            }
            , delay);
    } else {
        if (delayed) {
            // открытие модали поверх другой модали
            let modal = $("#myModal");
            if (modal.length === 1) {
                modal.modal('hide');
                return createModal(header, text, normalReset);
            }
        }
        return createModal(header, text, normalReset);
    }
}

function sendAjax(method, url, callback, attributes, isForm) {
    showWaiter();
    ajaxDangerReload();
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            deleteWaiter();
            ajaxNormalReload();
            callback(e);
        }).fail(function () {// noinspection JSUnresolvedVariable
            ajaxNormalReload();
            deleteWaiter();
            //callback(false)
        });
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            deleteWaiter();
            normalReload();
            callback(e);
        }).fail(function () {// noinspection JSUnresolvedVariable
            deleteWaiter();
            normalReload();
        });
    }
}

function sendSilentAjax(method, url, callback, attributes, isForm) {
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            callback(e);
        })
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            callback(e);
        }).fail(function () {// noinspection JSUnresolvedVariable
            callback(false)
        });
    }
}

function makeInformerModal(header, text, acceptAction, declineAction) {
    if (!text)
        text = '';
    let modal = $('<div class="modal fade mode-choose"><div class="modal-dialog text-center"><div class="modal-content"><div class="modal-header"><h3>' + header + '</h3></div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-success" type="button" id="acceptActionBtn">Ок</button></div></div></div>');
    $('body').append(modal);
    let acceptButton = modal.find('button#acceptActionBtn');
    modal.on('shown.bs.modal', function () {
        acceptButton.focus();
    });
    if (declineAction) {
        let declineBtn = $('<button class="btn btn-warning" role="button">Отмена</button>');
        declineBtn.insertAfter(acceptButton);
        declineBtn.on('click.custom', function () {
            normalReload();
            modal.modal('hide');
            declineAction();
        });
    }
    dangerReload();
    modal.modal({
        keyboard: false,
        backdrop: 'static',
        show: true
    });
    modal.on('hidden.bs.modal', function () {
        normalReload();
        modal.remove();
        $('div.wrap div.container, div.wrap nav').removeClass('blured');
    });
    $('div.wrap div.container, div.wrap nav').addClass('blured');

    acceptButton.on('click', function () {
        normalReload();
        modal.modal('hide');
        if (acceptAction) {
            acceptAction();
        } else {
            location.reload();
        }
    });

    return modal;
}


function ajaxDangerReload() {
    $(window).on('beforeunload.ajax', function () {
        return "Необходимо заполнить все поля на странице!";
    });
}

function ajaxNormalReload() {
    $(window).off('beforeunload.ajax');
}

function dangerReload() {
    $(window).on('beforeunload.message', function () {
        return "Необходимо заполнить все поля на странице!";
    });
}

function normalReload() {
    $(window).off('beforeunload');
}

function showWaiter() {
    let shader = $('<div class="shader"></div>');
    $('body').append(shader).css({'overflow': 'hidden'});

    $('div.wrap, div.flyingSumm, div.modal').addClass('blured');
    shader.showLoading();
}

function deleteWaiter() {
    $('div.wrap, div.flyingSumm, div.modal').removeClass('blured');
    $('body').css({'overflow': ''});
    let shader = $('div.shader');
    if (shader.length > 0)
        shader.hideLoading().remove();
}


function stringify(data) {
    if (typeof data === 'string') {
        return data;
    } else if (typeof data === 'object') {
        let answer = '';
        for (let i in data) {
            if (data.hasOwnProperty(i)) {
                answer += data[i] + '<br/>';
            }
        }
        return answer;
    }
}

// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerHandlerReload(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let header = data['header'] ? data['header'] : "Успешно";
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformerModal(header, message);
        }
    }
}

// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerHandler(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let header = data['header'] ? data['header'] : "Успешно";
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformerModal(header, message, function () {
            });
        }
    }
}

function simpleModalHandler(data) {
    if (data.status) {
        if (data.status === 1) {
            return makeModal(data.header, data.view);
        }
    }
    return null;
}


function sendAjaxWithFile(url, callback, form) {
    showWaiter();
    ajaxDangerReload();
    let formData = new FormData(form.get(0));
    $.ajax({
        data: formData,
        method: 'post',
        url: url,
        contentType: false,
        processData: false,
    }).done(function (e) {
        deleteWaiter();
        normalReload();
        callback(e);
    }).fail(function () {// noinspection JSUnresolvedVariable
        deleteWaiter();
        normalReload();
    });
}

// ========================================================== ИНФОРМЕР
// СОЗДАЮ ИНФОРМЕР
function makeInformer(type, header, body) {
    if (!body)
        body = '';
    const container = $('div#alertsContentDiv');
    const informer = $('<div class="alert-wrapper"><div class="alert alert-' + type + ' alert-dismissable my-alert"><div class="panel panel-' + type + '"><div class="panel-heading">' + header + '<button type="button" class="close">&times;</button></div><div class="panel-body">' + body + '</div></div></div></div>');
    informer.find('button.close').on('click.hide', function (e) {
        e.preventDefault();
        closeAlert(informer)
    });
    container.append(informer);
    showAlert(informer)
}

// ПОКАЗЫВАЮ ИНФОРМЕР
function showAlert(alertDiv) {
    // считаю расстояние от верха страницы до места, где располагается информер
    const topShift = alertDiv[0].offsetTop;
    const elemHeight = alertDiv[0].offsetHeight;
    let shift = topShift + elemHeight;
    alertDiv.css({'top': -shift + 'px', 'opacity': '0.1'});
    // анимирую появление информера
    alertDiv.animate({
        top: 0,
        opacity: 1
    }, 500, function () {
        // запускаю таймер самоуничтожения через 5 секунд
        /*setTimeout(function () {
            closeAlert(alertDiv)
        }, 5000);*/
    });

}

// СКРЫВАЮ ИНФОРМЕР
function closeAlert(alertDiv) {
    const elemWidth = alertDiv[0].offsetWidth;
    alertDiv.animate({
        left: elemWidth
    }, 500, function () {
        alertDiv.animate({
            height: 0,
            opacity: 0
        }, 300, function () {
            alertDiv.remove();
        });
    });
}


// обработка активаторов AJAX-запросов =================================================================================
function handleAjaxActivators() {
    "use strict";
    // найду активаторы AJAX-запросов
    let activators = $('.activator');
    activators.off('click.request');
    activators.on('click.request', function () {
        let action = $(this).attr('data-action');
        if (action) {
            // отправлю запрос на форму
            sendAjax(
                "get",
                action,
                simpleAnswerHandler
            )
        } else {
            makeInformer(
                "danger",
                "Ошибка",
                "Кнопке не назначено действие"
            )
        }
    });
}

$(function () {
    handleAjaxActivators();
});