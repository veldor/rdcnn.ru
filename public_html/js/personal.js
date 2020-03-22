let checkInterval;
let addConc;
let isAddConc = false;

function makeInstruction() {
    let text = '<h2 class="text-center">Выберите операционную систему</h2>' +
        '<div class="panel-group" id="accordion"><div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseWin">Windows</a></h3></div><div id="collapseWin" class="panel-collapse collapse"><div class="panel-body text-center"><div class="btn-group-vertical text-center"><a target="_blank" href="https://google.com" class="btn btn-default"><span class="text-info">Читать инструкцию</span></a><a target="_blank"  href="https://youtube.com" class="btn btn-default"><span class="text-success">Смотреть инструкцию</span></a></div></div></div></div>' +
        '<div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseMac">MacOs</a></h3></div><div id="collapseMac" class="panel-collapse collapse"><div class="panel-body"></div></div></div>' +
        '<div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseLin">Linux</a></h3></div><div id="collapseLin" class="panel-collapse collapse"><div class="panel-body"></div></div></div>' +
        '<div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseIos">iOs</a></h3></div><div id="collapseIos" class="panel-collapse collapse"><div class="panel-body"></div></div></div>' +
        '<div class="panel panel-default"><div class="panel-heading"><h3 class="text-center panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapseAnd">Android</a></h3></div><div id="collapseAnd" class="panel-collapse collapse"><div class="panel-body"></div></div></div></div>';
    makeModal("<h2 class='text-center'>Я скачал файл, что дальше</h2>", text, false, true, 1000);
}

$(function () {
        // назначу переменные для кнопок
    let downloadConclusionBtn = $('.downloadConclusionBtn');
    let printConclusionBtn = $('.printConclusionBtn');
    let conclusionNotReadyBtn = $('.conclusionNotReadyBtn');

    let downloadExecutionBtn = $('.downloadExecutionBtn');
    let executionNotReadyBtn = $('.executionNotReadyBtn');


    let availabilityTimeDivContainer = $('#availabilityTimeContainer');
    let availabilityTimeContainer = $('#availabilityTime');
    let removeReasonContainer = $('#removeReasonContainer');

    let clearDataBtn = $('.clearDataBtn');
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

    function checkAvailability(){
        sendSilentAjax('get', '/availability/check', handleAvailability);
    }

    function handleAvailability(data) {
        // если запрос успешен
        if(data['status'] === 1){
            // проверю, если есть заключение- активирую пункты о скачивании
            if(data['execution']){
                downloadExecutionBtn.removeClass('invisible');
                executionNotReadyBtn.addClass('invisible');
            }
            if(data['conclusion']){
                downloadConclusionBtn.removeClass('invisible');
                printConclusionBtn.removeClass('invisible');
                conclusionNotReadyBtn.addClass('invisible');
                removeReasonContainer.removeClass('invisible');
            }
            if(data['timeLeft']){
                availabilityTimeDivContainer.removeClass('invisible');
                availabilityTimeContainer.html(data['timeLeft'])
            }
            if(data['addConc']){
                // проверю, есть ли уже ссылки на скачивание дополнительных заключений
                if(isAddConc && addConc !== data['addConc']){
                    location.reload();
                }
                else{
                    addConc = data['addConc'];
                    isAddConc = true;
                }
            }
            // проверю, если есть файлы обследования- активирую пункты о скачивании
        }
        else if(data['status'] === 2){
            clearInterval(checkInterval);
            makeInformerModal('Результаты заключения не найдены', 'Возможно, они были удалены вами или истёк срок хранения. Для повторного получения результатов вы можете позвонить нам!');
        }
    }
    checkAvailability();
    checkInterval = setInterval(function () {
        checkAvailability();
    }, 60000);

    downloadExecutionBtn.on('click.showTooltip', function () {
        makeInstruction();
    });
});

