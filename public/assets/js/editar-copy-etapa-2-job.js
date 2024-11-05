$(document).ready(function() {

    setTimeout(function() {
        $(".showBriefing").css("height", 'auto');
        $(".showBriefing").css("opacity", '1');
        $(".spinner-border").css("display", 'none');
        $(".adjustTopDescricao").css("height", '0px');
        
    }, 800);

    const form = $("#formEdut");
    const submitButton = $("#submitButtonEdit");
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

            // envia o formulário após um pequeno intervalo de tempo
            setTimeout(() => {
            form.submit();
            }, 500);
        } else {
            form.addClass("was-validated");
        }
    });
    
    // Select2 Multiple
    $('.select2-multiple').select2({
        placeholder: "Selecionar sua(s) marca(s)",
        allowClear: true,
        templateSelection: function (data, container) {
            var cor = $(data.element).data('cor'); // pega a cor do data-cor
            $(container).css("background-color", cor); // define a cor de fundo do option
            return data.text;
        },
    });

    $('.select2-multiple-users').select2({
        placeholder: "Selecione seu(s) usuário(s)",
        allowClear: true,
        templateSelection: function (data, container) {
            $(container).css("background-color", '#222'); // define a cor de fundo do option
            return data.text;
        },
    });

    $('.select2-multiple-user').select2({
        placeholder: "Selecione seu(s) usuario(s)",
        allowClear: true,
        templateSelection: function (data, container) {
            $(container).css("background-color", '#222'); // define a cor de fundo do option
            return data.text;
        },
    });

    $('.select2-multiple-colaborador').select2({
        placeholder: "Selecione seu(s) colaborador(es)",
        allowClear: true,
        templateSelection: function (data, container) {
            $(container).css("background-color", '#222'); // define a cor de fundo do option
            return data.text;
        },
    });

});