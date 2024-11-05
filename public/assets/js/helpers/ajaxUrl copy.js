function updateURLParams(dateRange, url) {
  var ordem = $("select[name=ordem]").find(":selected").attr("value");
  var status = $("select[name=status]").find(":selected").attr("value");
  var priority = $("select[name=priority]").find(":selected").attr("value");
  var criador = $("select[name=criador]").find(":selected").attr("value");
  var marca = $("select[name=marca]").find(":selected").attr("value");
  var agencia = $("select[name=agencia]").find(":selected").attr("value");
  var titulo = $("#titulo").val();
  var progresso = $("#progresso").val();

  var queryParams = new URLSearchParams(window.location.search);

  if(url == '/admin/ordem' || url == '/colaborador/ordem'){
    queryParams.set('agencia', agencia);
  }

  queryParams.set('ordem', ordem);
  queryParams.set('status', status);
  queryParams.set('priority', priority);
  queryParams.set('dateRange', dateRange);
  queryParams.set('page', 1);
  queryParams.set('criador', criador);
  queryParams.set('marca', marca);
  queryParams.set('titulo', titulo);
  // queryParams.set('progresso', progresso);

  var newURL = window.location.pathname + '?' + queryParams.toString();
  history.replaceState(null, null, newURL);

  $("#loadingIndicator").show();
  $(".showTableJobs").hide();
  $.ajax({
    url: url,
    type: "get",
    dataType: "html",
    data: {
      ordem,
      status,
      priority,
      criador,
      agencia,
      marca,
      titulo,
      progresso,
      dateRange: $(".filter-daterangepicker").val(),
    },
    success: function (response) {
      $("#jobOrdem").html(response);
    },
    complete: function () {
      $("#loadingIndicator").hide();
      $(".showTableJobs").show();
    },
    error: function () {
      $("#loadingIndicator").hide();
      $(".showTableJobs").show();
    }
  });
}


//ordenar
var orderSort = [];
function updateColumnOrder(order) {
  table.find("thead tr").html(order.map(index => {
    return table.find("th[data-column-index='" + index + "']").get(0).outerHTML;
  }).join(""));
  table.find("tbody tr").each(function() {
    var row = $(this);
    row.html(order.map(index => {
      return row.find("td[data-column-index='" + index + "']").get(0).outerHTML;
    }).join(""));
  });

  var columnOrderInput = $("#columnOrderInput");
  columnOrderInput.val(order.join(","));

  orderSort = order;
}

// Função para atualizar a ordem das colunas no modal
function updateModalColumnOrder(columnOrder) {
  var $sortableColumns = $("#sortableColumns");
  columnOrder.forEach(function(index) {
      var $listItem = $sortableColumns.find('li[data-column-index="' + index + '"]');
      $listItem.appendTo($sortableColumns);
  });
}

$(document).ready(function() {
  var defaultOrder = Array.from(Array($("#sortableColumns li").length).keys());

  // Carregar a ordem das colunas do localStorage ou usar a ordem padrão
  var columnOrder = orderSort;
  if (!columnOrder) {
    columnOrder = defaultOrder;
  }

  // Inicializar a ordenação das colunas usando jQuery UI Sortable
  $("#sortableColumns").sortable({
    update: function(event, ui) {
      columnOrder = $("#sortableColumns li").map(function() {
          return parseInt($(this).attr("data-column-index"));
      }).get();
      updateColumnOrder(columnOrder)
      updateModalColumnOrder(columnOrder)

    }
  });

  // $("#sortableHead, #sortableColumns").sortable({
  //   connectWith: ".sortable-connect",
  //   scroll: false,
  //   helper: 'clone',
  //   appendTo: 'body',
  //   start: function(event, ui) {
  //     ui.helper.addClass('ui-helper');
  //   },
  //   sort: function(event, ui) {
  //     var thIndex = ui.item.attr('data-column-index');
  //     var tdToMove = $("#sortableColumns td[data-column-index='" + thIndex + "']");
      
  //     var offsetX = ui.position.left - ui.originalPosition.left;
      
  //     // Definir a posição do td (célula da coluna) para acompanhar o movimento do th
  //     tdToMove.css({
  //       transform: `translateX(${offsetX}px)`
  //     });
  //   },
  //   receive: function(event, ui) { // Adicionar o evento receive
  //     var thIndex = ui.item.attr('data-column-index');
  //     var tdToMove = $("#sortableColumns td[data-column-index='" + thIndex + "']");
      
  //     // Reverter o movimento do th e td
  //     ui.sender.sortable('cancel');
  
  //     // Definir a posição do td (célula da coluna) para acompanhar o movimento do th
  //     var offsetX = ui.position.left - ui.originalPosition.left;
  //     tdToMove.css({
  //       transform: `translateX(${offsetX}px)`
  //     });
  //   },
  //   change: function(event, ui) { // Adicionar o evento change
  //     var thIndex = ui.item.attr('data-column-index');
  //     var tdToMove = $("#sortableColumns td[data-column-index='" + thIndex + "']");
      
  //     var offsetX = ui.position.left - ui.originalPosition.left;
      
  //     // Definir a posição do td (célula da coluna) para acompanhar o movimento do th durante o change
  //     tdToMove.css({
  //       transform: `translateX(${offsetX}px)`
  //     });
  //   },
  //   stop: function(event, ui) {
  //     var thIndex = ui.item.attr('data-column-index');
  //     var tdToMove = $("#sortableColumns td[data-column-index='" + thIndex + "']");
      
  //     // Remover o CSS aplicado ao td (célula da coluna) quando o arrastar é concluído
  //     tdToMove.css({
  //       transform: ''
  //     });
  //   },
  //   update: function(event, ui) {
  //     var thOrder = $("#sortableHead th[data-column-index]").map(function() {
  //       return parseInt($(this).attr("data-column-index"));
  //     }).get();
      
  //     // Restante do código (se houver)
  //     updateColumnOrder(thOrder);
  //     updateModalColumnOrder(thOrder);
      
  //     let token = $("input[name='_token']").val();
      
  //     $.ajax({
  //       url: '/job/ordem',
  //       type: 'POST',
  //       data: { 
  //         ordem: thOrder.join(','), 
  //         _token: token, 
  //       },
  //       success: function(data) {
  //         // Ação a ser realizada em caso de sucesso na requisição AJAX
  //       },
  //       error: function(xhr, status, error) {
  //         // Tratamento de erro, se necessário
  //       }
  //     });
  //   }
  // });


  $("#sortableHead").sortable({
    connectWith: ".sortable-connect",
    scroll: false,
    helper: 'clone',
    appendTo: 'body',
    start: function(event, ui) {
      ui.helper.addClass('ui-helper');
    },
    update: function(event, ui) {
      var thOrder = $("#sortableHead th[data-column-index]").map(function() {
        return parseInt($(this).attr("data-column-index"));
      }).get();

      var trs = $("#sortableColumns tr");
      trs.each(function() {
        var tds = $(this).find("td");
        var orderedTds = [];

        thOrder.forEach(function(index) {
          orderedTds.push(tds.filter("[data-column-index='" + index + "']").html());
        });

        tds.each(function(tdIndex) {
          $(this).html(orderedTds[tdIndex]);
        });
      });

      // Converter o array thOrder em uma string de números separados por vírgula
      var thOrderString = thOrder.join(',');

      let token = $("input[name='_token']").val();

      $.ajax({
        url: '/flow/job/ordem',
        type: 'POST',
        data: { 
          ordem: thOrderString, 
          _token: token, 
        },
        success: function(data) {
          // Ação a ser realizada em caso de sucesso na requisição AJAX
        },
        error: function(xhr, status, error) {
          // Tratamento de erro, se necessário
        }
      });

      // Restante do código (se houver)
      updateColumnOrder(thOrder);
      updateModalColumnOrder(thOrder);
    }
  });

  var urlParams = new URLSearchParams(window.location.search);
  var hasParams = urlParams.has('ordem') || urlParams.has('status') || urlParams.has('priority') ||
  urlParams.has('dateRange') || urlParams.has('criador') || urlParams.has('marca') || urlParams.has('titulo');
  var isPageOne = urlParams.get('page') === '1';
  var notPageOne = urlParams.has('page');
  if (!hasParams && isPageOne || !hasParams && !notPageOne) {
    $(".sortable").sortable({
        connectWith: ".sortable-connect",
        scroll: false,
        helper: 'clone',
        start: function(event, ui) {
            ui.helper.addClass('tr-helper');
        },
        update: function(event, ui) {
          var sortedData = $(this).sortable('toArray', {
            attribute: 'data-key',
            key: 'demanda_id'
          });

          $.ajax({
            type: 'POST',
            url: '/flow/demandas/ordem', // Substitua pela URL da rota que atualiza a ordem
            data: {
                demandas: sortedData,
                _token: token, 
            },
            success: function(response) {
                return response;
            },
            error: function(error) {
                console.log('Ocorreu um erro ao atualizar a ordem: ' + error.responseText);
            }
          });
        }
    });
  }
});