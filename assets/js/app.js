$(function () {
  $('.tabela-dados').DataTable({ language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' } });
  $('input, textarea').on('input', function () {
    if (!$(this).is('[type="email"], [type="password"]')) {
      this.value = this.value.toUpperCase();
    }
  });

  $('.campo-cep').mask('00000-000');
  $('input[name="telefone"], input[name="whatsapp"]').mask('(00) 00000-0000');

  $('.campo-cep').on('blur', function () {
    const cep = $(this).val().replace(/\D/g, '');
    if (cep.length !== 8) return;
    $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function (dados) {
      if (dados.erro) return;
      $('.campo-endereco').val((dados.logradouro || '').toUpperCase());
      $('.campo-bairro').val((dados.bairro || '').toUpperCase());
      $('.campo-cidade').val((dados.localidade || '').toUpperCase());
      $('.campo-uf').val((dados.uf || '').toUpperCase());
    });
  });
});
