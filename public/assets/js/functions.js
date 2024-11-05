let token = $("input[name='_token']").val();
tinymce.init({
    selector: ".ckText",
    language: "pt_BR",
    height: 350,
    browser_spellcheck: true,
    menubar: false,
    plugins: [
        "lists", 
        "charmap", "hr", "anchor", "pagebreak", "spellchecker",
        "searchreplace", "autolink", "wordcount", "visualblocks", "visualchars", "code", "fullscreen", "media", "nonbreaking",
        "save", "table", "contextmenu", "directionality", "template", "paste", "textcolor",
        "link", "image",
        "emoticons"
    ],
    toolbar:
        "insertfile undo redo | styleselect | bold strikethrough italic alignleft aligncenter alignright alignjustify bullist numlist outdent indent link image forecolor backcolor emoticons",

    automatic_uploads: true,
    image_advtab: true,
    image_dimensions: true,
    file_picker_types: 'image',
    file_picker_callback: function (cb, value, meta) {
        var input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.onchange = function () {
            var file = this.files[0];

            var formData = new FormData();
            formData.append('file', file);

            fetch('/upload-imagem', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': token
                },
            })
            .then(response => response.json())
            .then(data => {
                cb(data.location, { title: file.name });
            });
        };

        input.click();
    },

    setup: function(editor) {
        editor.on('drop', function(event) {
            var file = event.dataTransfer.files[0];
            if (file.type.startsWith('image/')) {
                var formData = new FormData();
                formData.append('file', file);

                fetch('/upload-imagem', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                })
                .then(response => response.json())
                .then(data => {
                    var imgBlob = editor.dom.select('img[src^="blob:"]');
                    imgBlob.forEach(img => img.remove());
                    editor.insertContent(`<img src="${data.location}" alt="${file.name}"/>`);
                  
                });
            }
        });

        editor.on('paste', function(event) {
            var items = (event.clipboardData || event.originalEvent.clipboardData).items;
            for (index in items) {
                var item = items[index];
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    var blob = item.getAsFile();
                    var formData = new FormData();
                    formData.append('file', blob);

                    fetch('/upload-imagem', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        var imgBlob = editor.dom.select('img[src^="blob:"]');
                        imgBlob.forEach(img => img.remove());
                        editor.insertContent(`<img src="${data.location}" alt="${blob.name}"/>`);
                    });
                }
            }
        });
    }
});

let selected = null;
let date = null;
url = window.location.pathname;
let slug = url.trim().split("/");

$("select[name=category_id]").on("change", function () {
    selected = $(this).find(":selected").attr("value");
    $.ajax({
        url: "/prioridade",
        type: "get",
        dataType: "html",
        data: {
            category_id: selected,
            _token: token,
        },
        success: function (response) {
            $("#jobs").html(response);
        },
    });
});

function setTinyMCEContentByClass(className, content) {
    tinymce.editors.forEach(function(editor) {
        if (editor.getElement().classList.contains(className)) {
            editor.setContent(content);
        }
    });
}

let idComentary;

$(".idComment").val("");
$(".idCommentResponse").val("");

function getComentary(id) {
    $(".idComment").val(id);
    $.ajax({
        url: "/comentario/editar/" + id,
        type: "GET",
        dataType: "json",
        data: {
            _token: token,
        },
        success: function (response) {
            var editorId = 'editor_' + id;
            var editor = tinymce.get(editorId);
            if (editor) {
                editor.setContent(response.comentary.descricao);
            }
            $('#selectGetCommentary_'+id).val('').trigger('change');

            if (response.comentary.marcado_usuario_id) {
                $("#selectGetCommentary_"+id).val(response.comentary.marcado_usuario_id).trigger('change');
            } else if (response.lidosIds.length > 0) {
                $('#selectGetCommentary_'+id).val(response.lidosIds).trigger('change');
            }
        },
    });
}


function getResponse(id) {
    $(".idCommentResponse").val(id);

    $.ajax({
        url: "/respostas/editar/" + id,
        type: "GET",
        dataType: "json",
        data: {
            _token: token,
        },
        success: function (response) {
            var editorId = 'editor_resposta_' + id;
            var editor = tinymce.get(editorId);
            if (editor) {
                editor.setContent(response.conteudo);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching commentary:', error);
        }
    });
}

var originalFormAction = $(".criarAjusteForm").attr("action");
var originalFormActionComentario = $(".criarAjusteFormComentario").attr("action");

$(document).ready(function () {
    $(".tablesorter").tablesorter({
        textExtraction: function(node) {
            return $(node).attr('data-date') || $(node).text();
        },
        dateFormat: 'ddmmyyyy',
    });

    setTimeout(function() {
        $(".timeline").css("height", 'auto');
        $(".timeline").css("opacity", '1');
        $("#myTable").css("display", 'table');
        $("#pie-chart").css("opacity", '1');
        $("#graph_bar").css("opacity", '1');
        $("#chart").css("opacity", '1');
        $("#graph_bar_prazos").css("opacity", '1');
        $(".spinner-border").css("display", 'none');
        $(".loadingHelper").css("height", '0px');
        $(".loadingHelper").css("marginBottom", '0px');
    }, 800);

    $("#progresso").on("input", function() {
        var value = $(this).val();
        $("#progressoValue").text(value);
      });

    $('.arounded').click(function() {
        $(this).toggleClass('aroundedCheck');
    });

    $('form.responseAjax, .saveOrdem').on('submit', function(event) {
        event.preventDefault();
        $('.verifyBtn').prop("disabled", true);

        var form = $(this);
        var formData = new FormData(form[0]);

        // Verifique se o TinyMCE está presente e obtenha o conteúdo do campo dinamicamente
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
            var $textarea = form.find('.ckText');
            var textareaId = $textarea.attr('id');
            if(textareaId){
                var content = tinymce.get(textareaId).getContent();
                // formData.delete($textarea.attr('name'));
                formData.append($textarea.attr('name'), content);
            }
        }


        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: response.message,
                        icon: 'success'
                    }).then(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message,
                        icon: 'error'
                    });
                    $('.verifyBtn').prop("disabled", false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('.verifyBtn').prop("disabled", false);

                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    Swal.fire({
                        title: 'Erro!',
                        text: jqXHR.responseJSON.message,
                        icon: 'error'
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Ocorreu um erro ao enviar o formulário.',
                        icon: 'error'
                    });
                }
            }
        });
    });

    $('.select2').select2({
        minimumResultsForSearch: Infinity,
        language: 'pt-BR'
    });

    $(document).on('click', 'tr.trLink', function(event) {
        var elementClicked = event.target;
        if ($(elementClicked).is("a") || $(elementClicked).is("i")) {
            return;
        }else{
            var link = $(this).attr("data-href"); // obter o link da linha clicada
            window.open(link, '_blank'); // abrir em nova aba ou janela

        }

    });

    $('.btnDanger').click(function(event) {
        event.stopPropagation(); // impede que o evento de clique na tr seja propagado para os elementos filhos
    });

    $('.cardSingleInitial .btns, .initialTitle button, .responseAjaxReq button').click(function(event) {
        event.stopPropagation();
    });
    
    $('.cardSingleInitial').click(function(event) {
        $(this).closest('.cardSingle').find('.contentRecorrencia').slideToggle();

        var dataId =  $(this).data('idag');
        var isRead =  $(this).data('isreadag' + dataId);
        if (isRead) {
            $.ajax({
                url: "/comentario/recorrencia/ler/" + dataId,
                type: "POST",
                dataType: "json",
                data: {
                    _token: token,
                },
                success: function (response) {
                    console.log('Comentário lido com sucesso.');
                },
            });
        }
    });

    $(".deleteBt").click(function (event) {
        event.preventDefault();
        let href = this.getAttribute("href");
        Swal.fire({
            title: "Deseja excluir?",
            icon: "question",
            text: "Por favor, certifique-se e depois confirme!",
            type: "warning",
            showCancelButton: !0,
            cancelButtonText: "Fechar",
            confirmButtonText: "Excluir",
            confirmButtonClass: "red-btn",
            reverseButtons: !0,
        }).then((result) => {
            if (result.value) {
                window.location = href;
            }
        });
    });
   

    // Verifica o tamanho da tela ao carregar a página
    if ($(window).width() <= 1080) {
        $('.simplebar-content-wrapper').css({
            'height': 'auto'
        });

        $('.simplebar-placeholder').css({
            'height': '0px',
            'width': '0px'
        });
    }

    if ($(window).width() <= 480) {
        Morris.Donut({
            size: 150
        });
    }

    //remover anexo
    $(".deleteArq").on("click", function (e) {
        e.preventDefault();
        var form = $(this).parents("form");
        Swal.fire({
            title: "Deseja excluir?",
            icon: "question",
            text: "Por favor, certifique-se e depois confirme!",
            type: "warning",
            showCancelButton: !0,
            cancelButtonText: "Fechar",
            confirmButtonText: "Excluir",
            confirmButtonClass: "red-btn",
            reverseButtons: !0,
        }).then((result) => {
            if (result.value) {
                form.submit();
            }
        });
    });

    $(".submitForm").on("click", function (e) {
        e.preventDefault();
        var form = $(this).parents("form");
        Swal.fire({
            title: "Deseja excluir?",
            icon: "question",
            text: "Por favor, certifique-se e depois confirme!",
            type: "warning",
            showCancelButton: !0,
            cancelButtonText: "Fechar",
            confirmButtonText: "Excluir",
            confirmButtonClass: "red-btn",
            reverseButtons: !0,
        }).then((result) => {
            if (result.value) {
                form.submit();
            }
        });
    });

    $(".submitFinalize").on("click", function (e) {
        e.preventDefault();
        var form = $(this).parents("form");
        Swal.fire({
            title: "Tem certeza de que deseja finalizar este job?",
            icon: "question",
            text: "Por favor, certifique-se e depois confirme!",
            type: "warning",
            showCancelButton: true,
            cancelButtonText: "Fechar",
            confirmButtonText: "Finalizar",
            confirmButtonClass: "green-btn",
            reverseButtons: true,
        }).then((result) => {
            if (result.value) {
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
            }
        });
    });

    $(".submitModal").on("click", function (e) {
        e.preventDefault();
        var form = $(this).parents("form");
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
    });


    $(".submitQuest").on("click", function (e) {
        e.preventDefault();
        var form = $(this).parents("form");
        Swal.fire({
            title: "Tem certeza de que deseja realizar esta ação?",
            icon: "question",
            text: "Por favor, certifique-se e depois confirme!",
            type: "warning",
            showCancelButton: true,
            cancelButtonText: "Fechar",
            confirmButtonText: "Confirmar",
            confirmButtonClass: "green-btn",
            reverseButtons: true,
        }).then((result) => {
            if (result.value) {
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
            }
        });
    });

    // const inputElement = document.querySelector('input[name="file[]"]');
    // const fileId = inputElement.getAttribute('class');
    // let url = inputElement.getAttribute("data-url");
    // const pond = FilePond.create(inputElement);
    // const id = document.querySelector("#textbox_id").value;
    // url = url.replace(":id", id);

    // document.querySelector(
    //     ".filepond--drop-label.filepond--drop-label label"
    // ).innerHTML = "";

    // FilePond.setOptions({
    //     labelIdle: `Arraste e solte seus arquivos aqui!`,
    //     labelInvalidField: "O campo contém arquivos inválidos",
    //     labelFileProcessingComplete: "Arquivo anexado",
    //     labelFileProcessing: "Carregando...",
    //     server: {
    //         url: url,
    //         headers: {
    //             "X-CSRF-TOKEN": token,
    //         },
    //     },

    //     onprocessfiles: function (file, progress) {
    //         Swal.fire({
    //             title: 'Sucesso!',
    //             text: "Arquivos anexados com sucesso!",
    //             icon: 'success',
    //             showDenyButton: false,
    //             showCancelButton: false,
    //             confirmButtonText: "Fechar",
    //         }).then(function() {
    //             if(fileId != 'notReload'){
    //                 location.reload();
    //             }// Atualizar a página após clicar em OK
    //         });
    //     },
    // });


});

var itemCount = Number($(".carousel li").length);

$(".carousel").slick({
    dots: false,
    arrows: true,
    speed: 500,
    fade: false,
    cssEase: "linear",
    prevArrow: '<span class="slide-arrow prev-arrow"></span>',
    nextArrow: '<span class="slide-arrow next-arrow"></span>',
    infinite: false,
    slidesToShow: 6,
    slidesToScroll: 6,
    initialSlide: itemCount > 6 ? itemCount - 6 : 0,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                arrows: true,
                dots: false,
                slidesToShow: 3,
                slidesToScroll: 3,
                initialSlide: itemCount > 3 ? itemCount - 3 : 0,
            },
        },
        {
            breakpoint: 1000,
            settings: {
                dots: false,
                arrows: false,
                slidesToShow: 2,
                slidesToScroll: 2,
                initialSlide: itemCount > 2 ? itemCount - 2 : 0,
            },
        },
        {
            breakpoint: 600,
            settings: {
                dots: false,
                arrows: false,
                slidesToShow: 1,
                slidesToScroll: 1,
                initialSlide: itemCount > 1 ? itemCount - 1 : 0,
            },
        },
    ],
});

var slickCarousel = $('.dashboardMarcas').slick({
    dots: false,
    arrows: true,
    speed: 500,
    fade: false,
    cssEase: "linear",
    autoplay: true,
    autoplaySpeed: 300000,
    prevArrow: '<span class="slide-arrow2 prev-arrow2"></span>',
    nextArrow: '<span class="slide-arrow2 next-arrow2"></span>',
    infinite: true,
    slidesToShow: 1,
    slidesToScroll: 1,
});


$('.dashboardMarcas').on('afterChange', function(event, slick, currentSlide){
    // Obtém o índice do slide atual
    var currentTabIndex = currentSlide;
    $('.textAlert').css('display', 'block');
    // Ativa a aba correspondente ao índice do slide atual
    // $('#myTab li:eq(' + currentTabIndex + ') a[data-toggle="tab"]').tab('show');


});


$('.carousel').slick('slickGoTo',itemCount,true);


$(function() {
    $('input.filter-daterangepicker').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Limpar', 
            fromLabel: 'De',
            toLabel: 'Até',
            customRangeLabel: 'Período personalizado',
            weekLabel: 'W',
            daysOfWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'],
            monthNames: [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ],
            firstDay: 0
        },
        isInvalidDate: function(date) {
            return (date.day() === 0 || date.day() === 6);
        },
        autoUpdateInput: true
    });

    $('input.filter-daterangepicker').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val(''); 

        picker.startDate = moment(); 
        picker.endDate = moment();   
        picker.updateView();         
        picker.container.find('.drp-selected').empty(); 
    });
});


(function () {
    'use strict'

    var forms = document.querySelectorAll('.needs-validation')

    Array.prototype.slice.call(forms)
        .forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
            }

            form.classList.add('was-validated')
        }, false)
        })
})()

