jQuery(document).ready(function ($) {
  // Carregar marcas
  $.ajax({
    url: ajaxurl,
    method: "POST",
    data: {
      action: "fipe_api_request",
      url: "https://fipe.parallelum.com.br/api/v2/cars/brands",
    },
    success: function (response) {
      if (response.success) {
        const brands = JSON.parse(response.data);
        $("#fipe_brand").append(
          '<option value="">Selecione uma marca</option>'
        );
        brands.forEach((brand) => {
          $("#fipe_brand").append(
            `<option value="${brand.code}">${brand.name}</option>`
          );
        });
      }
    },
  });

  // Atualizar modelos
  $("#fipe_brand").on("change", function () {
    const brandId = $(this).val(); // Captura o código da marca
    $("#fipe_model").prop("disabled", !brandId);
    $("#fipe_model").html('<option value="">Carregando...</option>');
    $("#fipe_year").html("").prop("disabled", true);
    $("#fipe_result #fipe_output").html("");

    if (brandId) {
      $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
          action: "fipe_api_request",
          url: `https://fipe.parallelum.com.br/api/v2/cars/brands/${brandId}/models`,
        },
        success: function (response) {
          if (response.success) {
            const models = JSON.parse(response.data);
            $("#fipe_model").html(
              '<option value="">Selecione um modelo</option>'
            );
            models.forEach((model) => {
              // Preenche o campo com o code do modelo
              $("#fipe_model").append(
                `<option value="${model.code}">${model.name}</option>`
              );
            });
          }
        },
      });
    }
  });

  // Atualizar anos
  $("#fipe_model").on("change", function () {
    const brandId = $("#fipe_brand").val(); // Código da marca
    const modelId = $(this).val(); // Código do modelo
    $("#fipe_year").prop("disabled", !modelId);
    $("#fipe_year").html('<option value="">Carregando...</option>');
    $("#fipe_result #fipe_output").html("");

    if (modelId) {
      $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
          action: "fipe_api_request",
          url: `https://fipe.parallelum.com.br/api/v2/cars/brands/${brandId}/models/${modelId}/years`,
        },
        success: function (response) {
          if (response.success) {
            const years = JSON.parse(response.data);
            $("#fipe_year").html('<option value="">Selecione um ano</option>');
            years.forEach((year) => {
              // Preenche o campo com o code do ano
              $("#fipe_year").append(
                `<option value="${year.code}">${year.name}</option>`
              );
            });
          }
        },
      });
    }
  });

  jQuery(document).ready(function ($) {
    // Exibir resultado com botão para salvar FIPE
    $("#fipe_year").on("change", function () {
      const brandId = $("#fipe_brand").val(); // Código da marca
      const modelId = $("#fipe_model").val(); // Código do modelo
      const yearId = $(this).val(); // Código do ano
      $("#fipe_result #fipe_output").html("Carregando...");

      if (yearId) {
        const apiUrl = `https://fipe.parallelum.com.br/api/v2/cars/brands/${brandId}/models/${modelId}/years/${yearId}`;
        $.ajax({
          url: ajaxurl,
          method: "POST",
          data: {
            action: "fipe_api_request",
            url: apiUrl,
          },
          success: function (response) {
            if (response.success) {
              fetch(apiUrl)
                .then((response) => response.json())
                .then((data) => {
                  const price = data.price || "N/A";
                  const referenceMonth = data.referenceMonth || "N/A";

                  // Exibir o valor e o mês de referência com o botão
                  $("#fipe_result #fipe_output").html(`
                                    <h4>Tabela FIPE do carro:</h4>
                                    <p style="font-size: 24px; font-weight: bold; color: #0073aa;">${price}</p>
                                    <p><strong>Mês de referência:</strong> ${referenceMonth}</p>
                                    <button id="save_fipe_button" type="button" class="button button-primary">Salvar FIPE</button>
                                `);

                  // Configurar o botão para salvar os dados
                  $("#save_fipe_button").on("click", function () {
                    $.ajax({
                      url: ajaxurl,
                      method: "POST",
                      data: {
                        action: "save_fipe_data",
                        security: fipe_save_vars.nonce,
                        post_id: $("input#post_ID").val(),
                        data: {
                          brand_id: brandId,
                          model_id: modelId,
                          year_id: yearId,
                          price: price,
                          referenceMonth: referenceMonth,
                        },
                      },
                      success: function (response) {
                        if (response.success) {
                          // Ocultar elementos e mostrar o resultado
                          $(".options-list").hide();
                          $("#save_fipe_button").hide();
                          $("#fipe_result").show();
                          $("#edit_fipe_button").show();

                          // Adicionar botão "Editar"
                          if (!$("#edit_fipe_button").length) {
                            $("#fipe_result").append(`
                                                        <button id="edit_fipe_button" type="button" class="button">Editar</button>
                                                    `);
                          }

                          // Configurar botão "Editar"
                          $("#edit_fipe_button").on("click", function () {
                            $(".options-list").show();
                            $("#save_fipe_button").show();
                            $("#edit_fipe_button").hide();
                            
                          });

                          alert("FIPE salva com sucesso!");
                        } else {
                          alert(
                            "Erro ao salvar a FIPE: " + response.data.message
                          );
                        }
                      },
                      error: function () {
                        alert("Erro ao conectar com o servidor.");
                      },
                    });
                  });
                });
            } else {
              $("#fipe_result #fipe_output").html("Erro ao buscar os dados.");
            }
          },
          error: function () {
            $("#fipe_result #fipe_output").html(
              "Erro ao conectar com o servidor."
            );
          },
        });
      }
    });
  });
});
