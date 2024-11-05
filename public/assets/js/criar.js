$(document).ready(function() {

    const form = $("#formCreate");
    const submitButton = $("#submitButtonCreate");
    submitButton.click(function(event) {
        if (form[0].checkValidity()) {
            event.preventDefault();

            Swal.fire({
                title: 'Aguarde',
                html: 'Enviando dados...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            setTimeout(() => {
            form.submit();
            }, 1000);
        } else {
            form.addClass("was-validated");
        }
    });

    $('.select2-multiple').select2({
        placeholder: "Selecione sua(s) marca(s)",
        allowClear: true,
        templateSelection: function (data, container) {
            var cor = $(data.element).data('cor'); // pega a cor do data-cor
            $(container).css("background-color", cor); // define a cor de fundo do option
            return data.text;
        },
    });

    $('.select2-multiple-user').select2({
        placeholder: "Selecione seu(s) usuário(s)",
        allowClear: true,
        templateSelection: function (data, container) {
            var cor = $(data.element).data('cor'); // pega a cor do data-cor
            $(container).css("background-color", '#222'); // define a cor de fundo do option
            return data.text;
        },
    });

    $('.select2-multiple-users').select2({
        placeholder: "Selecione seu(s) usuário(s)",
        allowClear: true,
        templateSelection: function (data, container) {
            var cor = $(data.element).data('cor'); // pega a cor do data-cor
            $(container).css("background-color", cor); // define a cor de fundo do option
            return data.text;
        },
    });

});