#!/bin/bash

# =============================================================================
# GENERADOR DE IMÁGENES AGROCONECTA PREMIUM (vía REST API) - CORREGIDO
# =============================================================================

PROJECT_ID="gen-lang-client-0640181145"
LOCATION="us-central1"
MODEL_ID="imagegeneration@006"
OUTPUT_DIR="web/modules/custom/jaraba_page_builder/images/previews"

# Obtener token de acceso de gcloud
ACCESS_TOKEN=$(gcloud auth print-access-token)
if [ -z "$ACCESS_TOKEN" ]; then
  echo "❌ Error: No se pudo obtener el Token de Acceso de gcloud."
  exit 1
fi

mkdir -p "$OUTPUT_DIR"

# Definición de Prompts
declare -A AGRO_PROMPTS
AGRO_PROMPTS["agroconecta-hero"]="Professional 8k photography of a modern organic farm at sunrise, morning mist, golden hour, luxury agricultural aesthetic, high focus."
AGRO_PROMPTS["agroconecta-features"]="Macro shot of an organic certification leaf with morning dew, vibrant greens #2E7D32, high-end product photography."
AGRO_PROMPTS["agroconecta-content"]="High-quality editorial portrait of a proud farmer with a basket of fresh organic produce, sunny field background."
AGRO_PROMPTS["agroconecta-cta"]="Close-up of a hand picking a sun-drenched organic apple from a branch, orchard background, high-contrast, fresh commerce."
AGRO_PROMPTS["agroconecta-faq"]="Symmetrical macro of a sunflower or organic sprout, clear sky, transparency and growth concept, clean composition."
AGRO_PROMPTS["agroconecta-gallery"]="Premium grid of diverse agricultural shots: soil, water, plants, harvest. Consistent warm lighting, organic textures."
AGRO_PROMPTS["agroconecta-map"]="Aerial drone photography of a sustainable farm with geometric crop patterns, green and earth contrast, clean lines."
AGRO_PROMPTS["agroconecta-pricing"]="Luxury wooden crates with premium organic produce, studio lighting, soft shadows, high-end subscription box presentation."
AGRO_PROMPTS["agroconecta-social-proof"]="Close-up of hands shaking in a golden wheat field, professional lighting, trust and partnership atmosphere."
AGRO_PROMPTS["agroconecta-stats"]="3D glass bar chart emerging from rich green soil, small plant growing, studio render, data-driven agriculture."
AGRO_PROMPTS["agroconecta-testimonials"]="Editorial portrait of an agricultural entrepreneur in a high-tech greenhouse, clean and inspiring aesthetic."

echo "🚀 Iniciando generación de imágenes para AgroConecta..."

for block in "${!AGRO_PROMPTS[@]}"; do
  prompt="${AGRO_PROMPTS[$block]}"
  echo "🎨 Generando: $block..."
  
  # Payload en una variable limpia
  PAYLOAD="{\"instances\":[{\"prompt\":\"$prompt\"}],\"parameters\":{\"sampleCount\":1,\"aspectRatio\":\"3:2\"}}"

  # Llamada cURL corregida (en una sola línea de ejecución)
  RESPONSE=$(curl -s -X POST "https://$LOCATION-aiplatform.googleapis.com/v1/projects/$PROJECT_ID/locations/$LOCATION/publishers/google/models/$MODEL_ID:predict" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json; charset=utf-8" \
    -d "$PAYLOAD")

  # Decodificar Base64 usando Python
  python3 -c "
import json, base64, sys
try:
    data = json.loads(sys.argv[1])
    if 'predictions' in data:
        b64_str = data['predictions'][0]['bytesBase64Encoded']
        with open(sys.argv[2], 'wb') as f:
            f.write(base64.b64decode(b64_str))
        print('✅ Guardado: ' + sys.argv[2])
    else:
        print('❌ Error API: ' + json.dumps(data)[:200] + '...')
except Exception as e:
    print('❌ Error procesando: ' + str(e))
" "$RESPONSE" "$OUTPUT_DIR/$block.png"

done

echo "🎉 Proceso de generación completado."
