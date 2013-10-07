(function () {
    'use strict';

    window.Claroline = window.Claroline || {};
    var calendar = window.Claroline.Calendar = {};

    calendar.initialize = function (context) {
        context = context || 'desktop';
        var clickedDate = null,
            id = null,
            url = null;

        $('.filter').click(function () {
            var numberOfChecked = $('.filter:checkbox:checked').length;
            var totalCheckboxes = $('.filter:checkbox').length;
            var selected = [];

            $('.filter:checkbox:checked').each(function () {
                selected.push($(this).attr('name'));
            });
            //if all checkboxes or none checkboxes are checked display all events
            if ((totalCheckboxes - numberOfChecked === 0) || (numberOfChecked === 0)) {
                $('#calendar').fullCalendar('clientEvents', function (eventObject) {
                    eventObject.visible = true;
                });
                $('#calendar').fullCalendar('rerenderEvents');
            } else {
                for (var i = 0; i < selected.length; i++) {
                    $('#calendar').fullCalendar('clientEvents', function (eventObject) {
                        var reg = new RegExp(' : ', 'g');
                        var title = eventObject.title.split(reg);

                        if (selected.indexOf(title[0]) < 0) {
                            eventObject.visible = false;
                            return true;
                        } else {
                            eventObject.visible = true;
                            return false;
                        }
                    });
                    $('#calendar').fullCalendar('rerenderEvents');
                }
            }
        });

        var dayClickWorkspace = function (date) {
            clickedDate = date;
            $('#deleteBtn').hide();
            $('#save').show();
            $('#updateBtn').hide();
            $('#agenda_form').find('input:text, input:password, input:file, select, textarea').val('');
            $('#agenda_form').find('input:radio, input:checkbox')
                .removeAttr('checked')
                .removeAttr('selected');
            $('#myModalLabel').text(Translator.get('agenda' + ':' + 'add_event'));
            var  currentDate = $.fullCalendar.formatDate( new Date(),'dd/MM/yyyy HH:mm'); 
            var pickedDate = $.fullCalendar.formatDate( date,'dd/MM/yyyy HH:mm');
            $('#agenda_form_start').val(pickedDate);

            if (pickedDate > currentDate) {
                $('#agenda_form_end').val(pickedDate);

            } else {
                $('#agenda_form_end').val(currentDate);
            }
            $('#myModal').modal();
        };
        var dayClickDesktop = function (date) {
            $('#deleteBtn').hide();
            $('#save').show();
            $('#updateBtn').hide();
            $('#agenda_form').find('input:text, input:password, input:file, select, textarea').val('');
            $('#agenda_form').find('input:radio, input:checkbox')
                .removeAttr('checked')
                .removeAttr('selected');
            var  currentDate = $.fullCalendar.formatDate(new Date(), 'dd/MM/yyyy HH:mm');
            var pickedDate = $.fullCalendar.formatDate(date, 'dd/MM/yyyy HH:mm');
            $('#agenda_form_start').val(pickedDate)
            if (pickedDate > currentDate) {
                $('#agenda_form_end').val(pickedDate);
            } else {
                $('#agenda_form_end').val(currentDate);
            }
            $('#myModal').modal();
        };
        var dayClickFunction = context === 'desktop' ? dayClickDesktop : dayClickWorkspace;

        $('#save').click(function () {
            if ($('#agenda_form_title').val() !== '') {
                $('#save').attr('disabled', 'disabled');
                var data = new FormData($('#myForm')[0]);
                data.append('agenda_form[description]',$('#agenda_form_description').val());
                var url = $('#myForm').attr('action');
                $.ajax({
                    'url': url,
                    'type': 'POST',
                    'data': data,
                    'processData': false,
                    'contentType': false,
                    'success': function (data, textStatus, xhr) {
                        if (xhr.status === 200) {
                            $('#myModal').modal('hide');
                            $('#save').removeAttr('disabled');
                            if (data.allDay === false) {
                                $('#calendar').fullCalendar(
                                    'renderEvent',
                                    {
                                        title: data.title,
                                        start: data.start,
                                        end: data.end,
                                        allDay: data.allDay,
                                        color: data.color
                                    },
                                    true // make the event 'stick'
                                );
                                $('#calendar').fullCalendar('unselect');
                            } else {
                                $.ajax({
                                    'url': $('a#taska').attr('href'),
                                    'type': 'GET',
                                    'success': function (data, textStatus, xhr) {
                                        $("#tasks").html(data);
                                        
                                    }
                                });
                            }
                        }
                    },
                    'error': function ( xhr, textStatus) {
                        if (xhr.status === 400) {//bad request
                            alert(Translator.get('agenda' + ':' + 'date_invalid'));
                            $('#save').removeAttr('disabled');
                        } else {
                            //if we got to this point we know that the controller
                            //did not return a json_encoded array. We can assume that
                            //an unexpected PHP error occured
                            alert(Translator.get('agenda' + ':' + 'error'));
                            $('#save').removeAttr('disabled');

                        }
                    }
                });
            } else {
                alert(Translator.get('agenda' + ':' + 'title'));
            }
        });

        $('#updateBtn').click(function () {
            $('#updateBtn').attr('disabled', 'disabled');
            var data = new FormData($('#myForm')[0]);
            data.append('id', id);
            data.append('agenda_form[description]',$('#agenda_form_description').val());
            var allDay = $('#agenda_form_allDay').attr('checked') === 'checked' ? 1 : 0;
            data.append('agenda_form[allDay]', allDay);
            url = $('a#update').attr('href');
            $.ajax({
                'url': url,
                'type': 'POST',
                'data': data,
                'processData': false,
                'contentType': false,
                'success': function (data, textStatus, xhr) {
                        $('#myModal').modal('hide');
                        $('#updateBtn').removeAttr('disabled');
                        $('#calendar').fullCalendar('refetchEvents');
                        $.ajax({
                            'url': $('a#taska').attr('href'),
                            'type': 'GET',
                            'success': function (data, textStatus, xhr) {
                                $("#tasks").html(data);
                                
                            }
                        });
                },
                'error': function ( xhr, textStatus) {
                    if (xhr.status === 400) {//bad request
                        alert(Translator.get('agenda' + ':' + 'error'));
                        $('#save').removeAttr('disabled');
                        $('#output').html(textStatus);
                    }
                }
            });
        });

        var deleteClick = function (id) {
            $('#deleteBtn').attr('disabled', 'disabled');
            url = $('a#delete').attr('href');
            $.ajax({
                'type': 'POST',
                'url': url,
                'data': {
                    'id': id
                },
                'success': function (data, textStatus, xhr) {
                    if (xhr.status === 200) {
                        $('#myModal').modal('hide');
                        $('#deleteBtn').removeAttr('disabled');
                        $('#calendar').fullCalendar('removeEvents', id);
                    }
                }
            });
        };

        /*
        * function to delete a task
        currentTarget = the object clicked
        */
        $('.delete-task').on('click', function (e) {
            var id = $(e.currentTarget).attr('data-event-id');
            deleteClick(id);
        });

        $('.update-task').on('click', function (e) {
            $('#save').hide();
            var list = e.target.parentElement.children;
            $('#myModal').modal('show');
            id = $(list[5])[0].innerHTML;
            $('#agenda_form').find('input:text, input:password, input:file, select, textarea').val('');
            $('#myModalLabel').text(Translator.get('agenda' + ':' + 'modify'));
            $('#agenda_form_title')
                .attr('value', $(e.target.parentElement.parentElement.children)[1].innerHTML);
            $('#agenda_form_start').val($(list[0])[0].innerHTML);
            $('#agenda_form_end').val($(list[1])[0].innerHTML);
            $('#agenda_form_description').val($(list[2])[0].innerHTML);
            if( $(list[3])[0].innerHTML == 1)
            {
                $('#agenda_form_allDay').attr('checked', true);
            }
             $('#agenda_form_priority option[value=' + $(list[3])[0].innerHTML + ']').attr('selected', 'selected');
        });
        function dropEvent(event, dayDelta, minuteDelta) {
            $.ajax({
                'url': $('a#move').attr('href'),
                'type': 'POST',
                'data': {
                    'id': event.id,
                    'dayDelta': dayDelta,
                    'minuteDelta': minuteDelta
                },
                'success': function (data, textStatus, xhr) {
                    //the response is in the data variable

                    if (xhr.status  === 200) {
                        alert(Translator.get('agenda' + ':' + 'event_update'));
                    } else if (xhr.status === 500) {//internal server error
                        alert(Translator.get('agenda' + ':' + 'error'));
                        $('#output').html(data);
                    }
                    else {
                        //if we got to this point we know that the controller
                        //did not return a json_encoded array. We can assume that
                        //an unexpected PHP error occured
                        alert(Translator.get('agenda' + ':' + 'error'));

                        //if you want to print the error:
                        $('#output').html(data);
                    }
                }
            });
        }
        function modifiedEvent(calEvent, context)
        {
            id = calEvent.id;
            $('#deleteBtn').show();
            $('#updateBtn').show();
            $('#save').hide();
            $('#myModalLabel').text(Translator.get('agenda' + ':' + 'modify'));
            var title = calEvent.title;
            if (context === 'desktop')
            {
                var reg = new RegExp('[:]+', 'g');
                title = title.split(reg);
                $('#agenda_form_title').attr('value', title[1]);
            } else
            {
                $('#agenda_form_title').attr('value', title);
            }
            $('#agenda_form_description').val(calEvent.description);
            $('#agenda_form_priority option[value=' + calEvent.color + ']').attr('selected', 'selected');
            var pickedDate = new Date(calEvent.start);
            $('#agenda_form_start').val($.fullCalendar.formatDate( pickedDate,'dd/MM/yyyy HH:mm'));
            if (calEvent.end === null){
                $('#agenda_form_end').val($.fullCalendar.formatDate( pickedDate,'dd/MM/yyyy HH:mm'));
            }
            else{
                var Enddate = new Date(calEvent.end);
                $('#agenda_form_end').val($.fullCalendar.formatDate( Enddate,'dd/MM/yyyy HH:mm'));
            }
            $('#agenda_form_allDay').attr('checked', false);
            $.ajaxSetup({
                'type': 'POST',
                'error': function (xhr, textStatus) {
                    if (xhr.status === 500) {//bad request
                        alert(Translator.get('agenda' + ':' + 'error'));
                    }
                }
            });

            $('#myModal').modal();
        }
        $('#deleteBtn').on('click', function () {
            deleteClick(id);
        });

        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay',      
            },
            columnFormat: {
                month: 'ddd',
                week: 'ddd d/M',
                day: 'dddd d/M'
            },
            buttonText: {
                prev: Translator.get('agenda' + ':' + 'prev'),
                next: Translator.get('agenda' + ':' + 'next'),
                prevYear: Translator.get('agenda' + ':' + 'prevYear'),
                nextYear: Translator.get('agenda' + ':' + 'nextYear'),
                today:    Translator.get('agenda' + ':' + 'today'),
                month:    Translator.get('agenda' + ':' + 'month'),
                week:     Translator.get('agenda' + ':' + 'week'),
                day:      Translator.get('agenda' + ':' + 'day')
            },
            monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet',
                'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
            monthNamesShort: ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août',
                'sept.', 'oct.', 'nov.', 'déc.'],
            dayNames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
            dayNamesShort: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
            editable: true,
            events: $('a#link').attr('href'),
            axisFormat: 'HH:mm',
            timeFormat: 'H(:mm)',
            agenda: 'h:mm{ - h:mm}',
            '': 'h:mm{ - h:mm}',
            minTime: 0,
            maxTime: 24,
            allDayText: 'all-day',
            allDaySlot: true,
            lazyFetching : true,
            eventDrop: function (event, dayDelta, minuteDelta) {
                dropEvent(event, dayDelta, minuteDelta);
            },
            dayClick: dayClickFunction,
            eventClick:  function (calEvent) {
                modifiedEvent(calEvent ,context);
                $('#calendar').fullCalendar( 'updateEvent', calEvent );
            },
            eventRender: function (event) {
                if (event.visible === false)
                {
                    return false;
                }
            },
            eventResize: function (event, dayDelta, minuteDelta, revertFunc) {
                $.ajax({
                    'url': $('a#move').attr('href'),
                    'type': 'POST',
                    'data' : {
                        'id': event.id,
                        'dayDelta': dayDelta,
                        'minuteDelta': minuteDelta
                    },
                    'success': function (data, textStatus, xhr) {
                        //the response is in the data variable

                        if (xhr.status === 200) {
                            alert(Translator.get('agenda' + ':' + 'event_update'));
                        }
                        else {
                            //if we got to this point we know that the controller
                            //did not return a json_encoded array. We can assume that
                            //an unexpected PHP error occured
                            alert(Translator.get('agenda' + ':' + 'error'));

                            //if you want to print the error:
                            $('#output').html(data);
                        }
                    }
                });
                if (!confirm('is this okay?')) {
                    revertFunc();
                }
            }
        });
    };
}) ();
