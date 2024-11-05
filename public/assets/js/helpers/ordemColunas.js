
$(document).ready(function() {
  var page = window.location.search.replace('?page=', '');

  $(window).on('hashchange', function() {
    if (window.location.search) {
      if (page == Number.NaN || page <= 0) {
        return false;
      } else {
        getData(page);
      }
    }
  });


  // $(document).on('click', '.pagination a', function(event) {
  //   event.preventDefault();

  //   const myurl = $(this).attr('href');
  //   const page = myurl.split('page=')[1];

  //   // Update the URL with the new page parameter
  //   const url = new URL(window.location.href);
  //   url.searchParams.set("page", page);
  //   history.replaceState(null, null, url.href);

  //   getData(page);
  // });

  function getData(page) {
    const queryParams = new URLSearchParams(window.location.search);
    const columnName = queryParams.get("coluna");
    const sortingOrder = queryParams.get("ordem");
    const perPage = queryParams.get("porpagina");
    const search = queryParams.get("search");
    const marca = queryParams.get("marca_id");
    const dateRange = queryParams.get("dateRange");
    const category_id = queryParams.get("category_id");
    const aprovada = queryParams.get("aprovada");
    // const agencia_id = queryParams.get("agencia_id");
    const colaborador_id = queryParams.get("colaborador_id");
    const in_tyme = queryParams.get("in_tyme");
    const ordem_filtro = queryParams.get("ordem_filtro");

    let url = "?page=" + page;

    if (columnName) {
      url += "&coluna=" + columnName;
    }
    if (sortingOrder) {
      url += "&ordem=" + sortingOrder;
    }
    if (perPage) {
      url += "&porpagina=" + perPage;
    }

    if(marca){
      url += '&marca_id=' + marca;
    }

    if(search){
      url += '&search=' + search;
    }

    if(dateRange){
      url += '&dateRange=' + dateRange;
    }

    if(category_id){
      url += '&category_id=' + category_id;
    }

    if(aprovada){
      url += '&aprovada=' + aprovada;
    }

    // if(agencia_id){
    //   url += '&agencia_id=' + agencia_id;
    // }

    if(colaborador_id){
      url += '&colaborador_id=' + colaborador_id;
    }

    if(in_tyme){
      url += '&in_tyme=' + in_tyme;
    }

    if(ordem_filtro){
      url += '&ordem_filtro=' + ordem_filtro;
    }

    $.ajax({
      url: url,
      type: "get",
      datatype: "html",
      success: function (response) {
        $("#jobOrdem").html(response);

        const thCells = $('.th-coluna');
        thCells.each(function() {
            const th = $(this);
            const columnActive = th.data('name');
            const columnOrdemActive = th.data('ordem');

            if (columnActive === columnName) {
                if (columnOrdemActive === 'desc') {
                    th.addClass('th-active');
                } else {
                    th.removeClass('th-active');
                }
            } else {
                th.removeClass('th-active');
            }
        });
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


  $(document).on('click', '#sortableHead th', function(event) {
    const columnName = $(this).find(".th-coluna").data("name");
    if(columnName !== undefined){
      const currentSortingOrder = $(this).find(".th-coluna").data("ordem");
      const newSortingOrder = currentSortingOrder === "asc" ? "desc" : "asc";

      const url = new URL(window.location.href);
      const currentUrlSortingOrder = url.searchParams.get("ordem");
      url.searchParams.set("coluna", columnName);
      url.searchParams.set("ordem", newSortingOrder);
      window.history.pushState({ path: url.href }, '', url.href);

      // Update data-ordem attribute to reflect the new sorting order
      $(this).find(".th-coluna").data("ordem", newSortingOrder);

      // Remove sorting classes from all headers
      $('#sortableHead th').removeClass("asc desc");

      // Add sorting class to the clicked header
      $(this).find(".th-coluna").addClass(newSortingOrder);

      // Get the current sorting order from URL

      // Check if the sorting order in URL is different from the new sorting order
      if (currentUrlSortingOrder !== newSortingOrder) {
        getData(page = 1);
        url.searchParams.set("page", 1);
        window.history.pushState({ path: url.href }, '', url.href);
      }
    }else{
      return false;
    }

  });
});
