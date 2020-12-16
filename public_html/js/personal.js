let checkInterval;
let conclusionsCount;
let serialNumber;
let isExecutionLoaded;
let activeNotificator;
let originalTitle;
let hintedYet = false;

function makeInstruction() {
    let text = '<h2 class="text-center">Выберите операционную систему</h2>' +
        '<div class="panel-group" id="accordion"><div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseWin">Windows</a></h3></div><div id="collapseWin" class="panel-collapse collapse"><div class="panel-body text-center"><div class="btn-group-vertical text-center"><a target="_blank" href="https://google.com" class="btn btn-default"><span class="text-info">Читать инструкцию</span></a><a target="_blank"  href="https://youtube.com" class="btn btn-default"><span class="text-success">Смотреть инструкцию</span></a></div></div></div></div>' +
        '<div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseMac">MacOs</a></h3></div><div id="collapseMac" class="panel-collapse collapse"><div class="panel-body"></div></div></div>' +
        '<div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseLin">Linux</a></h3></div><div id="collapseLin" class="panel-collapse collapse"><div class="panel-body"></div></div></div>' +
        '<div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseIos">iOs</a></h3></div><div id="collapseIos" class="panel-collapse collapse"><div class="panel-body"></div></div></div>' +
        '<div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseAnd">Android</a></h3></div><div id="collapseAnd" class="panel-collapse collapse"><div class="panel-body"></div></div></div></div>';
    makeModal("<h2 class='text-center'>Я скачал файл, что дальше</h2>", text, false, true, 1000);
}

function getConclusions(){
    return $('a.conclusion');
}

function startTitleNotificator() {
    console.log('start notificator');
    originalTitle = document.title;
    // пока пациент не перешел во вкладку- заставлю её менять название каждую секунду
    if(!activeNotificator){
        activeNotificator = setInterval(function () {
            if(document.title === originalTitle){
                document.title = 'Новые данные';
            }
            else{
                document.title = originalTitle;
            }
        }, 1000);
    }
}

function colorize(elem, stars) {
    let rate = elem.attr('data-rate');
    let color;
    switch (rate){
        case '1':
            color = 'text-danger';
            break;
        case '2':
            color = 'text-warning';
            break;
        case '3':
            color = 'text-info';
            break;
        case '4':
            color = 'text-success';
            break;
        case '5':
            color = 'text-success';
            break;
    }
    let slice = stars.slice(0, rate);
    slice.each(function (){
        $(this).html('<span class="' + color + ' glyphicon glyphicon-star"></span>');
    });
}

function sendRate(elem) {
    let rate = elem.attr('data-rate');
    sendAjax('post',
        '/rate',
        function () {
            // скрою оценку
            $('ul#rateList').hide();
            let message;
            switch (rate){
                case '1':
                case '2':
                    message = 'Нам очень жаль, что у вас остались неприятные впечатления от похода в наш центр. Если вы напишете, что именно вас не устроило, это поможет нам стать лучше и в дальнейшем не допустить такой ситуации';
                    break;
                case '3':
                    message = 'Спасибо за отзыв! Если вы напишете, что именно вас не устроило, это поможет нам стать лучше и в дальнейшем не допустить такой ситуации';
                    break;
                case '4':
                    message = 'Обидно, что чуть-чуть не дотянули до высшей оценки :) . Если вы напишете, что именно вас не устроило, это поможет нам стать лучше и в дальнейшем не допустить такой ситуации';
                    break;
                case '5':
                    message = 'Мы очень рады, что вам всё понравилось! Если у вас есть какие-то предложения или замечания- напишите нам, мы обязательно прочитаем :)';
                    break;
            }
            makeInformerModal('Спасибо',
                message,
                 function (){}
            )
        },
        {'rate' : rate}
        )
}

$(function () {
    // повешу отслеживание наведения фокуса на вкладку (для нотификации новых данных)
    $(window).on('focus.stopTimer', function () {
        console.log('focus');
        if(activeNotificator){
            clearInterval(activeNotificator);
            activeNotificator = null;
            document.title = originalTitle;
        }
    });
    // получу количество доступных заключений на данный момент
    conclusionsCount = getConclusions().length;
    // проверю, загружено ли заключение
    isExecutionLoaded = !!$('#executionReadyBtn'.length);
    let downloadExecutionBtn = $('.downloadExecutionBtn');


    let availabilityTimeDivContainer = $('#availabilityTimeContainer');
    let availabilityTimeContainer = $('#availabilityTime');

    let clearDataBtn = $('#clearDataBtn');
    clearDataBtn.on('click.clear', function () {
        makeInformerModal('Удалить данные?', 'Убрать все данные с сервера, чтобы никто не мог получить к ним доступ. Если вы удалите данные, то получить повторный доступ к ним вы сможете, обратившись в наш центр.', function () {
                clearInterval(checkInterval);
                sendAjax('post', '/user/delete-execution', function () {
                    makeInformerModal('Успешно', 'Все ваши данные удалены. Получить повторный доступ к ним вы сможете, обратившись в наш центр.');
                });
            }
            , function () {
            });
    });

    function checkAvailability() {
        sendSilentAjax('get', '/availability/check', handleAvailability);
    }

    function handleAvailability(data) {
        // если запрос успешен
        if (data['status'] === 1) {
            // проверю, если есть заключение- активирую пункты о скачивании
            if (data['execution']) {
                // если файлы загружены только что
                if(!isExecutionLoaded){
                    // удалю кнопку отсутствия файлов и добавлю кнопку скачивания
                   $('div#executionContainer').html("<a id='executionReadyBtn' href='/download/execution' class='btn btn-primary  btn btn-block margin with-wrap hinted'>Загрузить архив обследования</a>");
                   // оповещу о загрузке файлов
                    makeInformerModal(
                        'Новые данные',
                        'Архив обследования доступен для скачивания',
                        function () {
                        }
                    );
                    startTitleNotificator();
                    isExecutionLoaded = true;
                }
            }
            else{
                $('div#executionContainer').html("<a id='executionNotReadyBtn' class='btn btn-primary btn btn-block margin with-wrap disabled' role='button'>Архив обследования подготавливается</a>");
                isExecutionLoaded = false;
            }
            if (data.hasOwnProperty('conclusions') && data.conclusions) {
                let container = $('div#conclusionsContainer');
                // посчитаю количество заключений, если их больше, чем было до этого- оповещу пользователя о новом
                if (data.conclusions.length > conclusionsCount) {
                    makeInformerModal(
                        'Новые данные',
                        'Доступно для скачивания заключение по обследованию',
                        function () {
                        }
                    );
                    startTitleNotificator();
                    // актуализирую данные
                    let endCounter = conclusionsCount;
                    $(data.conclusions).each(function () {
                        // если ссылка на заключение уже есть- ничего не делаю, если нет- добавляю ссылку
                        if($('a.conclusion[data-href="' + this.file_name + '"]').length === 0){
                            ++endCounter;
                            container.append("<a href='/conclusion/" + this.file_name + "' class='btn btn-primary btn-block margin with-wrap conclusion' data-href='" + this.file_name + "'>Загрузить заключение врача <br/>" + this.execution_area + "</a><a target='_blank' href='/print-conclusion/" + this.file_name + "' class='btn btn-info btn-block margin with-wrap print-conclusion hinted' data-href='" + this.file_name + "'>Распечатать заключение врача<br/>" + this.execution_area + "</a>");
                        }
                    });
                }
                if (data.conclusions.length === 0 && conclusionsCount > 0) {
                    // заключений не найдено, проверю, если их и не было- ничего не делаю, если были- удаляю
                    // все пункты и добавляю заглушки
                    container.html("<a id='conclusionNotReadyBtn' class='btn btn-primary btn-block margin with-wrap disabled' role='button'>Заключение врача в работе</a>");
                }
                // тут придётся ещё раз пройтись по списку актуальных заключений, чтобы проверить, нет ли лишних ссылок, и если они есть- удалить их из списка
                let existentConclusions = getConclusions();
                if(existentConclusions.length > 0 && data.conclusions.length > 0){
                    // удалю заглушку об отсутствии заключений врача
                    $('a#conclusionNotReadyBtn').remove();
                    $(existentConclusions).each(function () {
                        let href = $(this).attr('data-href');
                        let existent = false;

                        let counter = 0;
                        while (data.conclusions[counter]){
                            if(data.conclusions[counter].file_name === href){
                                existent = true;
                                break;
                            }
                            counter++;
                        }
                        if(!existent){
                                $('a.print-conclusion[data-href="' + href + '"]').remove();
                                $(this.remove());
                            console.log('remove conclusion');
                        }
                    });
                }
                // обновлю данные о количестве заключений
                conclusionsCount = data.conclusions.length;
            }
            if (data['timeLeft']) {
                availabilityTimeDivContainer.removeClass('invisible hidden');
                availabilityTimeContainer.html(data['timeLeft']);
            }
            // проверю, если есть файлы обследования- активирую пункты о скачивании
        } else if (data['status'] === 2) {
            clearInterval(checkInterval);
            makeInformerModal('Результаты заключения не найдены', 'Возможно, они были удалены вами или истёк срок хранения. Для повторного получения результатов вы можете позвонить нам!');
        }
    }

    checkAvailability();
    checkInterval = setInterval(function () {
        checkAvailability();
    }, 10000);

    downloadExecutionBtn.on('click.showTooltip', function () {
        makeInstruction();
    });

    let stars = $('li.star');
    let starChild = $('li.star span');

    if(getCookie("rate_received")){
        $('ul#rateList').hide();
    }

    starChild.on('mousedown.sendRate, touchstart.do', function () {
        sendRate($(this));
    });

    stars.on('mousedown.sendRate, touchstart.do', function () {
        sendRate($(this));
    });
    stars.on('mouseenter.fire', function () {
        colorize($(this), stars);
    });
    stars.on('mouseleave.fire', function () {
        stars.html('<span class="glyphicon glyphicon-star-empty"></span>');
    });


    let reviewForm = $('form#reviewForm');


    if(getCookie("reviewed")){
        reviewForm.hide();
    }
    reviewForm.on('submit.send', function (e){
        e.preventDefault();
        sendAjax(
            'post',
            '/review',
            function () {
                $('form#reviewForm').hide();
                makeInformerModal('Спасибо',
                    "Благодарим за отыв!",
                    function (){}
                )
            },
            reviewForm,
            true
        )
    })

    if(!getCookie("rated")){
        $('.hinted').on('click.showRatingMessage', function () {
            if(!hintedYet){
                makeModal("<h2 class='text-center'>Оцените нашу работу</h2>", "<p>Спасибо за то, что вы пользуетесь нашими услугами.<br/>Мы будем счастливы, если вы найдёте минутку и оставите нам отзыв.<br/>Кликните по названию сайта ниже, чтобы перейти на сайт и заполнить форму отзыва</p> <div class='text-center margin'><a data-type='pd' class='btn btn-info rate-link' target='_blank' href='https://prodoctorov.ru/new/rate/lpu/48447/'>Оставить отзыв на ПроДокторов</a></div><div class='text-center margin'><a target='_blank' data-type='google' class='btn btn-info rate-link' href='https://search.google.com/local/writereview?placeid=ChIJHXcPvNHVUUER5IWxpxP1DfM'>Оставить отзыв на Google</a></div><div class='text-center margin'><a class='btn btn-info rate-link' data-type='ya' target='_blank' href='https://yandex.ru/maps/47/nizhny-novgorod/?add-review=true&ll=43.957299%2C56.325628&mode=search&oid=1122933423&ol=biz&z=14'>Оставить отзыв на Яндекс</div>", false, true, 1000);
                setCookie("rated", 1, 365);
                hintedYet = true;
                $('a.rate-link').on('click.rate', function (){
                    sendSilentAjax('post',
                        '/rated',
                        function (){},
                        {'type' : $(this).attr('data-type')});
                });
            }
        });
    }
    else{
        console.log('rated yet');
    }
});