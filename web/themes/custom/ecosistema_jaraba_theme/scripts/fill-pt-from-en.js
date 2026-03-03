#!/usr/bin/env node
/**
 * @file fill-pt-from-en.js
 * For any PT-BR string that has no translation, generate a basic PT translation
 * by copying the EN translation and applying common ES→PT pattern replacements.
 * This gives a usable PT translation that can be refined later.
 */
const fs = require('fs');
const path = require('path');

const TRANSLATIONS_DIR = path.join(__dirname, '..', 'translations');
const enPath = path.join(TRANSLATIONS_DIR, 'ecosistema_jaraba_theme.en.po');
const ptPath = path.join(TRANSLATIONS_DIR, 'ecosistema_jaraba_theme.pt-br.po');

// Read both files
const enContent = fs.readFileSync(enPath, 'utf8');
let ptContent = fs.readFileSync(ptPath, 'utf8');

// Extract EN msgid→msgstr map
const enMap = {};
const re = /msgid "(.*?)"\nmsgstr "(.*?)"/g;
let m;
while ((m = re.exec(enContent)) !== null) {
    if (m[1] && m[2]) enMap[m[1]] = m[2];
}

// ES→PT translation map for common patterns
const ES_TO_PT = {
    // Common words
    'Empleabilidad': 'Empregabilidade',
    'Emprendimiento': 'Empreendedorismo',
    'Comercio': 'Comércio',
    'Instituciones': 'Instituições',
    'Inteligencia': 'Inteligência',
    'búsqueda': 'busca',
    'empleo': 'emprego',
    'negocio': 'negócio',
    'herramientas': 'ferramentas',
    'ayudarte': 'ajudá-lo',
    'plataforma': 'plataforma',
    'vertical': 'vertical',
    'digitalizaci': 'digitalizaç',
    'certificaci': 'certificaç',
    'formación': 'formação',
    'profesional': 'profissional',
    'desarrollo': 'desenvolvimento',
    'territorio': 'território',
    'municipal': 'municipal',
    'inversores': 'investidores',
    'aceleración': 'aceleração',
    'validación': 'validação',
    'metodología': 'metodologia',
    'probada': 'comprovada',
    'impacto': 'impacto',
    'ecosistema': 'ecossistema',
    'programa': 'programa',
    'gratuito': 'gratuito',
    'gratis': 'grátis',
    'digital': 'digital',
    'urbano': 'urbano',
    'rural': 'rural',
    'local': 'local',
};

// For each empty PT msgstr, try to use the ES original with PT patterns
// or use the EN translation if no PT pattern works
let filled = 0;
const ptEntries = ptContent.split('\n\n');
let newPtContent = '';

for (const entry of ptEntries) {
    const msgidMatch = entry.match(/msgid "(.*?)"/);
    const msgstrMatch = entry.match(/msgstr "(.*?)"/);

    if (msgidMatch && msgstrMatch && msgstrMatch[1] === '' && msgidMatch[1] !== '') {
        const msgid = msgidMatch[1];

        // Try to create a PT translation from the Spanish original
        let ptTranslation = msgid;

        // Apply common ES→PT patterns
        for (const [es, pt] of Object.entries(ES_TO_PT)) {
            ptTranslation = ptTranslation.replace(new RegExp(es, 'g'), pt);
        }

        // If we got a meaningful change, use it; otherwise use EN
        if (ptTranslation !== msgid) {
            newPtContent += entry.replace('msgstr ""', `msgstr "${ptTranslation.replace(/"/g, '\\"')}"`) + '\n\n';
            filled++;
        } else if (enMap[msgid]) {
            // Use EN translation as fallback (better than empty)
            newPtContent += entry.replace('msgstr ""', `msgstr "${enMap[msgid]}"`) + '\n\n';
            filled++;
        } else {
            newPtContent += entry + '\n\n';
        }
    } else {
        newPtContent += entry + '\n\n';
    }
}

fs.writeFileSync(ptPath, newPtContent.trim() + '\n', 'utf8');
console.log(`Filled ${filled} PT-BR translations (mix of ES→PT patterns and EN fallbacks)`);

// Final count
const finalContent = fs.readFileSync(ptPath, 'utf8');
const empty = (finalContent.match(/msgstr ""\n/g) || []).length - 1;
console.log(`Remaining empty PT strings: ${empty}`);
