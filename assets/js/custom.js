$(function () {
    $('.datatable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json'
        }
    });

    $('.cep').mask('00000-000');
    $('.fone').mask('(00) 00000-0000');
    $('.data').mask('00/00/0000');

    $('.upper').on('input', function () {
        this.value = this.value.toUpperCase();
    });

    $('.cep').on('blur', function () {
        const cep = $(this).val().replace(/\D/g, '');
        if (cep.length !== 8) return;

        const row = $(this).closest('.form-row, form');
        $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function (dados) {
            if (dados.erro) return;
            row.find('.endereco').val((dados.logradouro || '').toUpperCase());
            row.find('.bairro').val((dados.bairro || '').toUpperCase());
            row.find('.cidade').val((dados.localidade || '').toUpperCase());
            row.find('.uf').val((dados.uf || '').toUpperCase());
        });
    });
});
