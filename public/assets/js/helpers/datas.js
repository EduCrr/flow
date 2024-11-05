$(document).ready(function() {

    var inputF = $('#inputF');
    var inputI = $('#inputI');

    function validarDataInicial(inputI, demandaInicio = '', inputF) {
        inputI.on('change', function() {
            var value = inputI.val();
            var valueF = inputF.val();
            var selectedDate = new Date(value);
            var currentDate = new Date();
            var dateI = new Date(value);
            var dateF = new Date(valueF);

            // currentDate.setHours('');
            var day = selectedDate.getDay();
            if (day === 0 || day === 6) {
                if(demandaInicio !== ''){
                    inputI.val(demandaInicio);
                }else{
                    inputI.val('');
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Data inválida',
                    text: 'Favor, selecione uma data em um dia útil.',
                });
            } else if (selectedDate < currentDate ) {
                if(demandaInicio !== ''){
                    inputI.val(demandaInicio);
                }else{
                    inputI.val('');
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Data inválida',
                    text: 'Favor, selecione em dias úteis e após a data atual',
                });
            }
            else if (dateI > dateF) {
                inputI.val('');
                Swal.fire({
                    icon: 'warning',
                    title: 'Data inválida',
                    text: 'A data inicial não pode ser posterior à data final.',
                });
                if(demandaInicio !== ''){
                    inputI.val(demandaInicio);
                }else{
                    inputI.val('');
                }
                return null;
            }
        });
    }

    function validarDataFinal(inputI, inputF) {

        inputF.on('change', function() {

            var brand = $('#selectColaboradores').val();
            var valueI = inputI.val();
            var valueF = inputF.val();
            var dateI = new Date(valueI);
            var dateF = new Date(valueF);
            var day = dateF.getDay();

            if(valueI == ''){
                Swal.fire({
                    icon: 'warning',
                    title: 'Data inválida',
                    text: 'Favor, selecione uma data inicial antes.',
                });
                inputF.val('');
                return null;
            }

            if (day === 0 || day === 6) {
                inputF.val('');
                Swal.fire({
                    icon: 'warning',
                    title: 'Data inválida',
                    text: 'Favor, selecione uma data final em um dia útil.',
                });
                return null;
            } else if (dateF.getTime() < dateI.getTime()) {
                inputF.val('');
                Swal.fire({
                    icon: 'warning',
                    title: 'Data inválida',
                    text: 'A data final não pode ser anterior à data inicial.',
                });
                return null;
            }

            $.ajax({
                url: '/flow/jobs/date',
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                data: { final: valueF, brand },
                success: function(data) {
                    if(data >= 4){
                        Swal.fire({
                            icon: 'info',
                            title: 'Atenção!',
                            text: `Já existem ${data} jobs programados para esta data de entrega.`,
                        });
                    }

                    return null;
                }
            });
        });
    }

    var demandaInicio = $('#demandaInicio').val();
    validarDataInicial(inputI, demandaInicio, inputF);
    validarDataFinal(inputI, inputF);

});
