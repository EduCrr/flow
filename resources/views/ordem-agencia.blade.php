@component('components.TabelaAgenciaComponent', ['demandas' => $demandas, 'arrayOrdem' => $arrayOrdem, 'ordem' => $ordem])@endcomponent
<script>
    $(document).ready(function() {
        $(document).ready(function() {
            $('#pagination').on('change', function() {
                var numberPage = $(this).val(); 
                $('#porpagina').val(numberPage); 
                var reset = @json($reset ?? false);
                var urlAtual = window.location.href;
                updatePaginationParams(urlAtual, true);
            });
        });
    });
</script>