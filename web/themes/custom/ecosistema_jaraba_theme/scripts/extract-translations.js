#!/usr/bin/env node
/**
 * @file extract-translations.js
 * Extracts {% trans %} strings from Twig templates and generates PO files
 * for English (en) and Portuguese (pt-br).
 *
 * Usage: node scripts/extract-translations.js
 * Output: translations/ecosistema_jaraba_theme.en.po
 *         translations/ecosistema_jaraba_theme.pt-br.po
 */
const fs = require('fs');
const path = require('path');
const glob = require('glob');

const THEME_DIR = path.resolve(__dirname, '..');
const TEMPLATES_DIR = path.join(THEME_DIR, 'templates');
const OUTPUT_DIR = path.join(THEME_DIR, 'translations');

// ============================================================================
// TRANSLATION DICTIONARIES
// ============================================================================

const EN = {
    // Navigation & UI Chrome
    'Soluciones': 'Solutions',
    'Precios': 'Pricing',
    'Casos de Éxito': 'Success Stories',
    'Iniciar sesión': 'Sign In',
    'Iniciar sesion': 'Sign In',
    'Cerrar sesión': 'Sign Out',
    'Registrarse': 'Sign Up',
    'Crear cuenta': 'Create Account',
    'Mi cuenta': 'My Account',
    'Configuración': 'Settings',
    'Panel de control': 'Dashboard',
    'Ir al inicio': 'Go to Home',
    'Ir a inicio': 'Go to Home',
    'Contactar soporte': 'Contact Support',
    'Volver': 'Go Back',
    'Ver más': 'See More',
    'Ver todo': 'See All',
    'Cargar más': 'Load More',
    'Buscar': 'Search',
    'Guardar': 'Save',
    'Cancelar': 'Cancel',
    'Eliminar': 'Delete',
    'Editar': 'Edit',
    'Enviar': 'Send',
    'Cerrar': 'Close',
    'Aceptar': 'Accept',
    'Siguiente': 'Next',
    'Anterior': 'Previous',
    'Continuar': 'Continue',
    'Copiar': 'Copy',
    'Descargar': 'Download',
    'Compartir': 'Share',

    // Skip links & Accessibility
    'Saltar al contenido principal': 'Skip to main content',
    'Skip to main content': 'Skip to main content',
    'Selector de idioma': 'Language selector',
    'Language selector': 'Language selector',
    'Menú principal': 'Main menu',
    'Menú de navegación': 'Navigation menu',
    'Abrir menú': 'Open menu',
    'Cerrar menú': 'Close menu',
    'Navegación principal': 'Main navigation',
    'Navegación secundaria': 'Secondary navigation',

    // Auth / Login
    'Informacion de bienvenida': 'Welcome information',
    'Información de bienvenida': 'Welcome information',
    'Bienvenido de nuevo': 'Welcome back',
    'Correo electrónico': 'Email',
    'Contraseña': 'Password',
    'Olvidé mi contraseña': 'Forgot my password',
    'Recordarme': 'Remember me',
    'Tu ecosistema digital para crecer': 'Your digital ecosystem to grow',

    // Hero & Landing
    'Plataforma SaaS': 'SaaS Platform',
    'Transforma tu ecosistema digital': 'Transform your digital ecosystem',
    'Solicita una demo': 'Request a Demo',
    'Conoce el método': 'Discover the Method',
    'Cumplimiento RGPD': 'GDPR Compliance',
    'Copiloto IA Integrado': 'Integrated AI Copilot',
    'Datos Cifrados': 'Encrypted Data',
    'Metodología Probada': 'Proven Methodology',

    // Verticals
    'Empleabilidad': 'Employability',
    'Emprendimiento': 'Entrepreneurship',
    'Comercio': 'Commerce',
    'Instituciones': 'Institutions',
    'Servicios': 'Services',
    'Legal Intelligence': 'Legal Intelligence',

    // Footer
    'Ecosistema Jaraba': 'Jaraba Ecosystem',
    'Plataforma': 'Platform',
    'Empresa': 'Company',
    'Legal': 'Legal',
    'Sobre nosotros': 'About Us',
    'Centro de Ayuda': 'Help Center',
    'Contacto': 'Contact',
    'Blog': 'Blog',
    'Privacidad': 'Privacy',
    'Términos': 'Terms',
    'Cookies': 'Cookies',
    'Todos los derechos reservados.': 'All rights reserved.',
    'Powered by': 'Powered by',

    // Error Pages
    'Acceso restringido': 'Access Restricted',
    'Acceso denegado': 'Access Denied',
    'Pagina no encontrada': 'Page Not Found',
    'Página no encontrada': 'Page Not Found',
    'No tienes permisos para acceder a esta seccion. Contacta con el administrador si crees que es un error.':
        'You do not have permission to access this section. Contact the administrator if you believe this is an error.',
    'Inicia sesion para acceder a este recurso o registrate para crear una cuenta.':
        'Sign in to access this resource or register to create an account.',
    'La pagina que buscas no existe o fue movida a otra ubicacion.':
        'The page you are looking for does not exist or has been moved.',
    'Ilustracion de acceso restringido': 'Restricted access illustration',
    'Ilustracion de pagina no encontrada': 'Page not found illustration',
    'Buscar en el sitio': 'Search the site',
    'Termino de busqueda': 'Search term',
    'Enlaces rapidos': 'Quick links',
    'Tambien puedes visitar': 'You can also visit',

    // Content Hub / Blog
    'Artículos recientes': 'Recent Articles',
    'Categorías': 'Categories',
    'Publicado el': 'Published on',
    'Leer más': 'Read More',
    'Tiempo de lectura': 'Reading time',
    'minutos': 'minutes',
    'min de lectura': 'min read',
    'Artículos relacionados': 'Related Articles',
    'Compartir artículo': 'Share Article',
    'Comentarios': 'Comments',
    'Escribir un comentario': 'Write a comment',

    // Dashboard
    'Resumen': 'Summary',
    'Actividad reciente': 'Recent Activity',
    'Estadísticas': 'Statistics',
    'Notificaciones': 'Notifications',
    'Sin notificaciones': 'No notifications',

    // Pricing
    'Elige tu plan': 'Choose Your Plan',
    'Mensual': 'Monthly',
    'Anual': 'Annual',
    'Gratis': 'Free',
    'Profesional': 'Professional',
    'Empresas': 'Enterprise',
    'Comenzar gratis': 'Start Free',
    'Contactar ventas': 'Contact Sales',
    'Más popular': 'Most Popular',

    // Contact
    'Nombre': 'Name',
    'Apellidos': 'Last Name',
    'Teléfono': 'Phone',
    'Mensaje': 'Message',
    'Asunto': 'Subject',
    'Selecciona una opción': 'Select an option',

    // Legal
    'Expedientes Juridicos': 'Legal Cases',
    'Nuevo Expediente': 'New Case',
    'Activos': 'Active',
    'En Espera': 'Pending',
    'Completados': 'Completed',
    'Este Mes': 'This Month',
    'No hay expedientes activos. Crea tu primer expediente para comenzar.':
        'No active cases. Create your first case to get started.',

    // Success Cases
    'Resultados': 'Results',
    'Ver caso completo': 'View Full Case',
    'Testimonios': 'Testimonials',

    // AI Features
    'Copiloto IA': 'AI Copilot',
    'Inteligencia Artificial': 'Artificial Intelligence',
    'Diagnóstico': 'Diagnostics',
    'Recomendaciones': 'Recommendations',
    'Automatización': 'Automation',

    // Misc
    'Cargando...': 'Loading...',
    'Error': 'Error',
    'Éxito': 'Success',
    'Advertencia': 'Warning',
    'Información': 'Information',
    'Confirmar': 'Confirm',
    'Obligatorio': 'Required',
    'Opcional': 'Optional',
    'Si': 'Yes',
    'No': 'No',
    'Filtrar': 'Filter',
    'Ordenar por': 'Sort by',
    'Fecha': 'Date',
    'Tipo': 'Type',
    'Estado': 'Status',
    'Acciones': 'Actions',

    // Email
    'Si tienes preguntas, responde a este correo &mdash; nuestro equipo est&aacute; para ayudarte.':
        'If you have questions, reply to this email &mdash; our team is here to help.',

    // Copilot
    'Hablar con el copiloto': 'Talk to the Copilot',
    'Escribir mensaje...': 'Write a message...',
    'Vista previa': 'Preview',
    'Historial': 'History',
    'Nuevas ideas': 'New ideas',
    'Analizar': 'Analyze',
    'Generar': 'Generate',
    'Optimizar': 'Optimize',

    // Forms
    'Los campos marcados con * son obligatorios': 'Fields marked with * are required',
    'Formulario enviado correctamente': 'Form submitted successfully',
    'Ha ocurrido un error. Inténtalo de nuevo.': 'An error occurred. Please try again.',
    'Acepto la política de privacidad': 'I accept the privacy policy',
    'Acepto los términos de uso': 'I accept the terms of use',

    // Maintenance
    'Sitio en mantenimiento': 'Site Under Maintenance',
    'Estamos realizando mejoras. Vuelve pronto.': 'We are making improvements. Come back soon.',

    // Progress / Gamification
    'Progreso': 'Progress',
    'Nivel': 'Level',
    'Logros': 'Achievements',
    'Completar perfil': 'Complete Profile',
    'Paso': 'Step',
    'de': 'of',

    // Metasite
    'Elige tu camino': 'Choose Your Path',
    'Descubre nuestras soluciones': 'Discover Our Solutions',
    'Nuestros clientes': 'Our Clients',
    'Prueba social': 'Social Proof',
};

const PT = {
    // Navigation & UI Chrome
    'Soluciones': 'Soluções',
    'Precios': 'Preços',
    'Casos de Éxito': 'Casos de Sucesso',
    'Iniciar sesión': 'Entrar',
    'Iniciar sesion': 'Entrar',
    'Cerrar sesión': 'Sair',
    'Registrarse': 'Cadastre-se',
    'Crear cuenta': 'Criar conta',
    'Mi cuenta': 'Minha conta',
    'Configuración': 'Configurações',
    'Panel de control': 'Painel de Controle',
    'Ir al inicio': 'Ir ao início',
    'Ir a inicio': 'Ir ao início',
    'Contactar soporte': 'Contatar suporte',
    'Volver': 'Voltar',
    'Ver más': 'Ver mais',
    'Ver todo': 'Ver tudo',
    'Cargar más': 'Carregar mais',
    'Buscar': 'Pesquisar',
    'Guardar': 'Salvar',
    'Cancelar': 'Cancelar',
    'Eliminar': 'Excluir',
    'Editar': 'Editar',
    'Enviar': 'Enviar',
    'Cerrar': 'Fechar',
    'Aceptar': 'Aceitar',
    'Siguiente': 'Próximo',
    'Anterior': 'Anterior',
    'Continuar': 'Continuar',
    'Copiar': 'Copiar',
    'Descargar': 'Baixar',
    'Compartir': 'Compartilhar',

    // Skip links & Accessibility
    'Saltar al contenido principal': 'Pular para o conteúdo principal',
    'Skip to main content': 'Pular para o conteúdo principal',
    'Selector de idioma': 'Seletor de idioma',
    'Language selector': 'Seletor de idioma',
    'Menú principal': 'Menu principal',
    'Menú de navegación': 'Menu de navegação',
    'Abrir menú': 'Abrir menu',
    'Cerrar menú': 'Fechar menu',
    'Navegación principal': 'Navegação principal',
    'Navegación secundaria': 'Navegação secundária',

    // Auth / Login
    'Informacion de bienvenida': 'Informações de boas-vindas',
    'Información de bienvenida': 'Informações de boas-vindas',
    'Bienvenido de nuevo': 'Bem-vindo de volta',
    'Correo electrónico': 'E-mail',
    'Contraseña': 'Senha',
    'Olvidé mi contraseña': 'Esqueci minha senha',
    'Recordarme': 'Lembrar-me',
    'Tu ecosistema digital para crecer': 'Seu ecossistema digital para crescer',

    // Hero & Landing
    'Plataforma SaaS': 'Plataforma SaaS',
    'Transforma tu ecosistema digital': 'Transforme seu ecossistema digital',
    'Solicita una demo': 'Solicite uma demo',
    'Conoce el método': 'Conheça o método',
    'Cumplimiento RGPD': 'Conformidade RGPD',
    'Copiloto IA Integrado': 'Copiloto IA Integrado',
    'Datos Cifrados': 'Dados Criptografados',
    'Metodología Probada': 'Metodologia Comprovada',

    // Verticals
    'Empleabilidad': 'Empregabilidade',
    'Emprendimiento': 'Empreendedorismo',
    'Comercio': 'Comércio',
    'Instituciones': 'Instituições',
    'Servicios': 'Serviços',
    'Legal Intelligence': 'Inteligência Jurídica',

    // Footer
    'Ecosistema Jaraba': 'Ecossistema Jaraba',
    'Plataforma': 'Plataforma',
    'Empresa': 'Empresa',
    'Legal': 'Legal',
    'Sobre nosotros': 'Sobre nós',
    'Centro de Ayuda': 'Central de Ajuda',
    'Contacto': 'Contato',
    'Blog': 'Blog',
    'Privacidad': 'Privacidade',
    'Términos': 'Termos',
    'Cookies': 'Cookies',
    'Todos los derechos reservados.': 'Todos os direitos reservados.',
    'Powered by': 'Powered by',

    // Error Pages
    'Acceso restringido': 'Acesso restrito',
    'Acceso denegado': 'Acesso negado',
    'Pagina no encontrada': 'Página não encontrada',
    'Página no encontrada': 'Página não encontrada',
    'No tienes permisos para acceder a esta seccion. Contacta con el administrador si crees que es un error.':
        'Você não tem permissão para acessar esta seção. Entre em contato com o administrador se acreditar que é um erro.',
    'Inicia sesion para acceder a este recurso o registrate para crear una cuenta.':
        'Faça login para acessar este recurso ou registre-se para criar uma conta.',
    'La pagina que buscas no existe o fue movida a otra ubicacion.':
        'A página que você procura não existe ou foi movida para outro local.',
    'Ilustracion de acceso restringido': 'Ilustração de acesso restrito',
    'Ilustracion de pagina no encontrada': 'Ilustração de página não encontrada',
    'Buscar en el sitio': 'Pesquisar no site',
    'Termino de busqueda': 'Termo de pesquisa',
    'Enlaces rapidos': 'Links rápidos',
    'Tambien puedes visitar': 'Você também pode visitar',

    // Content Hub / Blog
    'Artículos recientes': 'Artigos recentes',
    'Categorías': 'Categorias',
    'Publicado el': 'Publicado em',
    'Leer más': 'Leia mais',
    'Tiempo de lectura': 'Tempo de leitura',
    'minutos': 'minutos',
    'min de lectura': 'min de leitura',
    'Artículos relacionados': 'Artigos relacionados',
    'Compartir artículo': 'Compartilhar artigo',
    'Comentarios': 'Comentários',
    'Escribir un comentario': 'Escrever um comentário',

    // Dashboard
    'Resumen': 'Resumo',
    'Actividad reciente': 'Atividade recente',
    'Estadísticas': 'Estatísticas',
    'Notificaciones': 'Notificações',
    'Sin notificaciones': 'Sem notificações',

    // Pricing
    'Elige tu plan': 'Escolha seu plano',
    'Mensual': 'Mensal',
    'Anual': 'Anual',
    'Gratis': 'Grátis',
    'Profesional': 'Profissional',
    'Empresas': 'Empresas',
    'Comenzar gratis': 'Começar grátis',
    'Contactar ventas': 'Contatar vendas',
    'Más popular': 'Mais popular',

    // Contact
    'Nombre': 'Nome',
    'Apellidos': 'Sobrenome',
    'Teléfono': 'Telefone',
    'Mensaje': 'Mensagem',
    'Asunto': 'Assunto',
    'Selecciona una opción': 'Selecione uma opção',

    // Legal
    'Expedientes Juridicos': 'Expedientes Jurídicos',
    'Nuevo Expediente': 'Novo Expediente',
    'Activos': 'Ativos',
    'En Espera': 'Em Espera',
    'Completados': 'Concluídos',
    'Este Mes': 'Este Mês',
    'No hay expedientes activos. Crea tu primer expediente para comenzar.':
        'Não há expedientes ativos. Crie seu primeiro expediente para começar.',

    // Success Cases
    'Resultados': 'Resultados',
    'Ver caso completo': 'Ver caso completo',
    'Testimonios': 'Depoimentos',

    // AI Features
    'Copiloto IA': 'Copiloto IA',
    'Inteligencia Artificial': 'Inteligência Artificial',
    'Diagnóstico': 'Diagnóstico',
    'Recomendaciones': 'Recomendações',
    'Automatización': 'Automação',

    // Misc
    'Cargando...': 'Carregando...',
    'Error': 'Erro',
    'Éxito': 'Sucesso',
    'Advertencia': 'Aviso',
    'Información': 'Informação',
    'Confirmar': 'Confirmar',
    'Obligatorio': 'Obrigatório',
    'Opcional': 'Opcional',
    'Si': 'Sim',
    'No': 'Não',
    'Filtrar': 'Filtrar',
    'Ordenar por': 'Ordenar por',
    'Fecha': 'Data',
    'Tipo': 'Tipo',
    'Estado': 'Status',
    'Acciones': 'Ações',

    // Email
    'Si tienes preguntas, responde a este correo &mdash; nuestro equipo est&aacute; para ayudarte.':
        'Se você tiver dúvidas, responda a este e-mail &mdash; nossa equipe está aqui para ajudar.',

    // Copilot
    'Hablar con el copiloto': 'Falar com o copiloto',
    'Escribir mensaje...': 'Escrever mensagem...',
    'Vista previa': 'Pré-visualização',
    'Historial': 'Histórico',
    'Nuevas ideas': 'Novas ideias',
    'Analizar': 'Analisar',
    'Generar': 'Gerar',
    'Optimizar': 'Otimizar',

    // Forms
    'Los campos marcados con * son obligatorios': 'Os campos marcados com * são obrigatórios',
    'Formulario enviado correctamente': 'Formulário enviado com sucesso',
    'Ha ocurrido un error. Inténtalo de nuevo.': 'Ocorreu um erro. Tente novamente.',
    'Acepto la política de privacidad': 'Aceito a política de privacidade',
    'Acepto los términos de uso': 'Aceito os termos de uso',

    // Maintenance
    'Sitio en mantenimiento': 'Site em manutenção',
    'Estamos realizando mejoras. Vuelve pronto.': 'Estamos fazendo melhorias. Volte em breve.',

    // Progress / Gamification
    'Progreso': 'Progresso',
    'Nivel': 'Nível',
    'Logros': 'Conquistas',
    'Completar perfil': 'Completar perfil',
    'Paso': 'Passo',
    'de': 'de',

    // Metasite
    'Elige tu camino': 'Escolha seu caminho',
    'Descubre nuestras soluciones': 'Descubra nossas soluções',
    'Nuestros clientes': 'Nossos clientes',
    'Prueba social': 'Prova social',
};

// ============================================================================
// EXTRACTION
// ============================================================================

function extractTransStrings(dir) {
    const strings = new Set();
    const pattern = path.join(dir, '**', '*.twig').replace(/\\/g, '/');

    let files;
    try {
        files = glob.sync(pattern);
    } catch (e) {
        // Fallback: manual recursive scan
        files = [];
        function walk(d) {
            for (const f of fs.readdirSync(d)) {
                const full = path.join(d, f);
                if (fs.statSync(full).isDirectory()) walk(full);
                else if (f.endsWith('.twig')) files.push(full);
            }
        }
        walk(dir);
    }

    for (const file of files) {
        const content = fs.readFileSync(file, 'utf8');

        // {% trans %}...{% endtrans %}
        const transRe = /\{%\s*trans\s*%\}([\s\S]*?)\{%\s*endtrans\s*%\}/g;
        let match;
        while ((match = transRe.exec(content)) !== null) {
            const str = match[1].trim();
            if (str && !str.includes('{%') && !str.includes('{{')) {
                strings.add(str);
            }
        }

        // 'text'|t filter
        const tFilterRe = /'([^']+)'\s*\|\s*t\b/g;
        while ((match = tFilterRe.exec(content)) !== null) {
            strings.add(match[1]);
        }
    }

    return Array.from(strings).sort();
}

// ============================================================================
// PO GENERATION
// ============================================================================

function generatePO(langcode, langName, translations, strings) {
    const now = new Date().toISOString().replace('T', ' ').replace(/\.\d+Z$/, '+0000');

    let po = `# ${langName} translations for Ecosistema Jaraba Theme.
# Generated by extract-translations.js
# Copyright (C) 2026 Jaraba Impact Platform
#
msgid ""
msgstr ""
"Project-Id-Version: ecosistema_jaraba_theme 1.0\\n"
"POT-Creation-Date: ${now}\\n"
"PO-Revision-Date: ${now}\\n"
"Language: ${langcode}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=utf-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"

`;

    let translated = 0;
    let untranslated = 0;

    for (const str of strings) {
        const translation = translations[str];
        po += `msgid "${escPO(str)}"\n`;
        if (translation) {
            po += `msgstr "${escPO(translation)}"\n`;
            translated++;
        } else {
            po += `msgstr ""\n`;
            untranslated++;
        }
        po += '\n';
    }

    console.log(`${langcode}: ${translated} translated, ${untranslated} untranslated of ${strings.length} total`);
    return po;
}

function escPO(str) {
    return str
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n');
}

// ============================================================================
// MAIN
// ============================================================================

function main() {
    console.log('Extracting translatable strings from Twig templates...');
    const strings = extractTransStrings(TEMPLATES_DIR);
    console.log(`Found ${strings.length} unique translatable strings.\n`);

    if (!fs.existsSync(OUTPUT_DIR)) {
        fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    }

    // English
    const enPO = generatePO('en', 'English', EN, strings);
    const enPath = path.join(OUTPUT_DIR, 'ecosistema_jaraba_theme.en.po');
    fs.writeFileSync(enPath, enPO, 'utf8');
    console.log(`Written: ${enPath}`);

    // Portuguese (Brazil)
    const ptPO = generatePO('pt-br', 'Portuguese (Brazil)', PT, strings);
    const ptPath = path.join(OUTPUT_DIR, 'ecosistema_jaraba_theme.pt-br.po');
    fs.writeFileSync(ptPath, ptPO, 'utf8');
    console.log(`Written: ${ptPath}`);

    // List untranslated strings for review
    const untranslatedEN = strings.filter(s => !EN[s]);
    const untranslatedPT = strings.filter(s => !PT[s]);

    if (untranslatedEN.length > 0) {
        console.log(`\n⚠ ${untranslatedEN.length} strings missing EN translation:`);
        untranslatedEN.forEach(s => console.log(`  - "${s}"`));
    }
    if (untranslatedPT.length > 0) {
        console.log(`\n⚠ ${untranslatedPT.length} strings missing PT translation:`);
        untranslatedPT.forEach(s => console.log(`  - "${s}"`));
    }
}

main();
