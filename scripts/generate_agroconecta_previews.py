import os
import argparse
from google.cloud import aiplatform
from vertexai.preview.vision_models import ImageGenerationModel

# =============================================================================
# CONFIGURACIÓN DE AGROCONECTA PREMIUM
# =============================================================================
DEFAULT_PROJECT_ID = "tu-proyecto-id"
LOCATION = "us-central1"
OUTPUT_DIR = "web/modules/custom/jaraba_page_builder/images/previews"

# Estilo: Fotografía profesional, iluminación natural, paleta #2E7D32 y #4CAF50
# Motivos: Agricultura moderna, sostenibilidad, frescura.
AGRO_PROMPTS = {
    "agroconecta-hero": "Cinematic high-end photography of a vast organic farm at dawn. Morning mist over green crop rows, soft golden sun on the horizon. 8k resolution, professional architectural photography, agricultural luxury.",
    "agroconecta-features": "Macro photography of an organic certification stamp on a fresh green leaf. Crisp details, dew drops, vibrant green tones (#2E7D32), bright natural lighting, professional product shot.",
    "agroconecta-content": "High-quality documentary photo of a local farmer proudly holding a basket of premium organic vegetables. Authentic smile, sunny field background, warm earth tones, sharp focus.",
    "agroconecta-cta": "A close-up of a hand reaching for a perfect, sun-drenched organic fruit on a branch. Vibrant green leaves, blurred orchard background, high-contrast, inviting commerce aesthetic.",
    "agroconecta-faq": "Symmetrical close-up of a sunflower or organic sprout against a clear blue sky. Symbolizing transparency and growth. Clean composition, professional agricultural photography.",
    "agroconecta-gallery": "A premium grid layout of diverse agricultural close-ups: soil, water, plants, and harvest. Consistent warm lighting, organic textures, high-end farm-to-table mood.",
    "agroconecta-map": "Minimalist aerial photography of a sustainable farm with circular crop patterns. High contrast between green fields and earth, clean lines, professional drone photography.",
    "agroconecta-pricing": "Luxurious wooden crates branded with AgroConecta logo, filled with organized organic produce. Studio lighting, soft shadows, high-end subscription box presentation.",
    "agroconecta-social-proof": "A close-up of hands shaking in a golden wheat field. Professional lighting, shallow depth of field, symbolizing trust and partnership in the agricultural sector.",
    "agroconecta-stats": "A modern 3D glass bar chart emerging from a rich green soil base with a small plant growing next to it. Professional studio render, soft reflections, data-driven agriculture.",
    "agroconecta-testimonials": "An editorial portrait of a young agricultural entrepreneur in a high-tech greenhouse. Soft lighting, professional equipment in background, clean and inspiring aesthetic."
}

def run_generation(project_id):
    print(f"🚀 Iniciando generación en proyecto: {project_id}")
    
    try:
        aiplatform.init(project=project_id, location=LOCATION)
        model = ImageGenerationModel.from_pretrained("imagen@005")
    except Exception as e:
        print(f"❌ Error inicializando Vertex AI: {e}")
        return

    if not os.path.exists(OUTPUT_DIR):
        os.makedirs(OUTPUT_DIR)
        print(f"📁 Directorio creado: {OUTPUT_DIR}")

    for block, prompt in AGRO_PROMPTS.items():
        output_path = os.path.join(OUTPUT_DIR, f"{block}.png")
        print(f"🎨 Generando {block}...")
        
        try:
            images = model.generate_images(
                prompt=prompt,
                number_of_images=1,
                aspect_ratio="3:2",
                guidance_scale=21
            )
            images[0].save(location=output_path, include_generation_parameters=False)
            print(f"✅ Guardado: {output_path}")
        except Exception as e:
            print(f"⚠️ Falló {block}: {e}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--project", default=DEFAULT_PROJECT_ID, help="Google Cloud Project ID")
    args = parser.parse_args()
    
    if args.project == "tu-proyecto-id":
        print("❌ Por favor, proporciona un Project ID válido con --project YOUR_ID")
    else:
        run_generation(args.project)
