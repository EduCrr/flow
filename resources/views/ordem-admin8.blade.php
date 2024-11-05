@component('components.TabelaAdmin8Component', ['demandas' => $demandas, 'arrayOrdem' => $arrayOrdem,  'sortableEnabled' => false, 'ordem' => $ordem])@endcomponent
<script src="{{ asset('assets/js/helpers/ajaxUrl.js') }}" ></script>
<script>
    $(document).ready(function() {
        $('#pagination').on('change', function() {
            var numberPage = $(this).val();
            $('#porpagina').val(numberPage);
            var reset = @json($reset ?? false);
            var urlAtual = window.location.href;
            updatePaginationParams(urlAtual, true);
        });
    });
</script>
