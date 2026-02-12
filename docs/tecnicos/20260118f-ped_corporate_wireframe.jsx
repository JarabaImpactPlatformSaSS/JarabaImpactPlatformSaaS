import React, { useState, useEffect } from 'react';
import { Building2, Users, Target, TrendingUp, Award, Newspaper, Briefcase, Mail, ChevronRight, ExternalLink, Menu, X, ArrowRight, Check, Globe, Zap, Shield, Heart, BarChart3, FileText, Download, Play, Quote, MapPin, Phone, Clock, Linkedin, Twitter } from 'lucide-react';

// Paleta corporativa PED
const colors = {
  primary: '#1B4F72',
  secondary: '#17A589',
  accent: '#E67E22',
  dark: '#1A1A2E',
  gray: '#64748B',
  lightGray: '#F1F5F9',
  white: '#FFFFFF',
  gradient: 'linear-gradient(135deg, #1B4F72 0%, #17A589 100%)'
};

// Componente Header
const Header = ({ currentPage, setCurrentPage }) => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  useEffect(() => {
    const handleScroll = () => setIsScrolled(window.scrollY > 50);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const navItems = [
    { id: 'home', label: 'Inicio' },
    { id: 'about', label: 'Sobre Nosotros' },
    { id: 'impact', label: 'Impacto' },
    { id: 'partners', label: 'Partners' },
    { id: 'press', label: 'Prensa' },
    { id: 'careers', label: 'Careers' },
  ];

  return (
    <header 
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        isScrolled ? 'bg-white/95 backdrop-blur-md shadow-lg py-3' : 'bg-transparent py-5'
      }`}
    >
      <div className="max-w-7xl mx-auto px-6 flex items-center justify-between">
        {/* Logo */}
        <div 
          className="flex items-center gap-3 cursor-pointer group"
          onClick={() => setCurrentPage('home')}
        >
          <div 
            className="w-12 h-12 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105"
            style={{ background: colors.gradient }}
          >
            <span className="text-white font-black text-lg">ped</span>
          </div>
          <div className={`transition-colors ${isScrolled ? 'text-gray-900' : 'text-white'}`}>
            <div className="font-bold text-sm tracking-wide">PLATAFORMA DE</div>
            <div className="font-bold text-sm tracking-wide">ECOSISTEMAS DIGITALES</div>
          </div>
        </div>

        {/* Desktop Nav */}
        <nav className="hidden lg:flex items-center gap-8">
          {navItems.map(item => (
            <button
              key={item.id}
              onClick={() => setCurrentPage(item.id)}
              className={`text-sm font-medium transition-all hover:opacity-100 ${
                currentPage === item.id 
                  ? 'opacity-100' 
                  : 'opacity-70 hover:opacity-100'
              } ${isScrolled ? 'text-gray-700' : 'text-white'}`}
            >
              {item.label}
              {currentPage === item.id && (
                <div 
                  className="h-0.5 mt-1 rounded-full"
                  style={{ background: colors.accent }}
                />
              )}
            </button>
          ))}
        </nav>

        {/* CTA */}
        <button
          onClick={() => setCurrentPage('contact')}
          className="hidden lg:flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold text-sm transition-all hover:scale-105 hover:shadow-lg"
          style={{ 
            background: colors.accent, 
            color: colors.white 
          }}
        >
          <Mail size={16} />
          Contacto
        </button>

        {/* Mobile Menu Button */}
        <button 
          className="lg:hidden"
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
        >
          {mobileMenuOpen ? (
            <X className={isScrolled ? 'text-gray-900' : 'text-white'} />
          ) : (
            <Menu className={isScrolled ? 'text-gray-900' : 'text-white'} />
          )}
        </button>
      </div>

      {/* Mobile Menu */}
      {mobileMenuOpen && (
        <div className="lg:hidden absolute top-full left-0 right-0 bg-white shadow-xl p-6">
          {navItems.map(item => (
            <button
              key={item.id}
              onClick={() => {
                setCurrentPage(item.id);
                setMobileMenuOpen(false);
              }}
              className="block w-full text-left py-3 text-gray-700 font-medium border-b border-gray-100"
            >
              {item.label}
            </button>
          ))}
          <button
            onClick={() => {
              setCurrentPage('contact');
              setMobileMenuOpen(false);
            }}
            className="w-full mt-4 py-3 rounded-lg font-semibold text-white"
            style={{ background: colors.accent }}
          >
            Contacto
          </button>
        </div>
      )}
    </header>
  );
};

// Componente Footer
const Footer = ({ setCurrentPage }) => (
  <footer className="bg-gray-900 text-white">
    <div className="max-w-7xl mx-auto px-6 py-16">
      <div className="grid md:grid-cols-4 gap-12 mb-12">
        {/* Logo & Description */}
        <div className="md:col-span-1">
          <div className="flex items-center gap-3 mb-4">
            <div 
              className="w-10 h-10 rounded-lg flex items-center justify-center"
              style={{ background: colors.gradient }}
            >
              <span className="text-white font-black text-sm">ped</span>
            </div>
            <span className="font-bold">PED S.L.</span>
          </div>
          <p className="text-gray-400 text-sm leading-relaxed">
            Infraestructura digital para el desarrollo rural y la transformación de territorios.
          </p>
          <div className="flex gap-4 mt-6">
            <a href="#" className="text-gray-400 hover:text-white transition-colors">
              <Linkedin size={20} />
            </a>
            <a href="#" className="text-gray-400 hover:text-white transition-colors">
              <Twitter size={20} />
            </a>
          </div>
        </div>

        {/* Empresa */}
        <div>
          <h4 className="font-semibold mb-4 text-white">Empresa</h4>
          <ul className="space-y-3">
            {['Sobre Nosotros', 'Equipo', 'Ecosistema', 'Impacto'].map(item => (
              <li key={item}>
                <button className="text-gray-400 hover:text-white text-sm transition-colors">
                  {item}
                </button>
              </li>
            ))}
          </ul>
        </div>

        {/* Legal */}
        <div>
          <h4 className="font-semibold mb-4 text-white">Legal</h4>
          <ul className="space-y-3">
            {['Aviso Legal', 'Privacidad', 'Cookies', 'Transparencia'].map(item => (
              <li key={item}>
                <button className="text-gray-400 hover:text-white text-sm transition-colors">
                  {item}
                </button>
              </li>
            ))}
          </ul>
        </div>

        {/* Contacto */}
        <div>
          <h4 className="font-semibold mb-4 text-white">Contacto</h4>
          <ul className="space-y-3 text-sm text-gray-400">
            <li className="flex items-start gap-2">
              <MapPin size={16} className="mt-1 flex-shrink-0" />
              <span>Calle Ejemplo 123, 14005 Córdoba, España</span>
            </li>
            <li className="flex items-center gap-2">
              <Phone size={16} />
              <span>+34 957 000 000</span>
            </li>
            <li className="flex items-center gap-2">
              <Mail size={16} />
              <span>info@plataformadeecosistemas.es</span>
            </li>
          </ul>
        </div>
      </div>

      {/* Bottom Bar */}
      <div className="border-t border-gray-800 pt-8">
        <div className="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500">
          <div>
            <strong>Plataforma de Ecosistemas Digitales S.L.</strong> | CIF: B-XXXXXXXX | 
            Registro Mercantil de Córdoba, Tomo XXX, Folio XXX
          </div>
          <div>© 2026 PED S.L. Todos los derechos reservados.</div>
        </div>
      </div>
    </div>
  </footer>
);

// ==================== PÁGINAS ====================

// HOME PAGE
const HomePage = ({ setCurrentPage }) => {
  const stats = [
    { value: '+30', label: 'Años de experiencia', icon: Clock },
    { value: '+100M€', label: 'Fondos gestionados', icon: BarChart3 },
    { value: '5', label: 'Verticales SaaS', icon: Globe },
    { value: '+50', label: 'Municipios impactados', icon: MapPin },
  ];

  const audiences = [
    { icon: TrendingUp, title: 'Soy Inversor', desc: 'Due diligence y métricas', link: 'transparency' },
    { icon: Building2, title: 'Soy Institución', desc: 'Alianzas y convenios', link: 'partners' },
    { icon: Newspaper, title: 'Soy Prensa', desc: 'Sala de prensa y recursos', link: 'press' },
    { icon: Briefcase, title: 'Quiero Trabajar Aquí', desc: 'Ofertas y cultura', link: 'careers' },
  ];

  const verticals = [
    { name: 'Empleabilidad', color: '#3498DB', desc: 'LMS + Job Board + Matching' },
    { name: 'Emprendimiento', color: '#9B59B6', desc: 'Diagnóstico + Mentoría' },
    { name: 'AgroConecta', color: '#27AE60', desc: 'Marketplace agroalimentario' },
    { name: 'ComercioConecta', color: '#E67E22', desc: 'Digitalización comercio local' },
    { name: 'ServiciosConecta', color: '#E74C3C', desc: 'Marketplace servicios' },
  ];

  return (
    <div>
      {/* Hero */}
      <section 
        className="min-h-screen flex items-center relative overflow-hidden"
        style={{ background: colors.gradient }}
      >
        {/* Background Pattern */}
        <div className="absolute inset-0 opacity-10">
          <div className="absolute top-20 left-10 w-72 h-72 rounded-full border-2 border-white" />
          <div className="absolute bottom-20 right-10 w-96 h-96 rounded-full border border-white" />
          <div className="absolute top-1/2 left-1/2 w-64 h-64 rounded-full border border-white transform -translate-x-1/2 -translate-y-1/2" />
        </div>

        <div className="max-w-7xl mx-auto px-6 py-32 relative z-10">
          <div className="max-w-4xl">
            <div 
              className="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium mb-8"
              style={{ background: 'rgba(255,255,255,0.15)', color: 'white' }}
            >
              <Zap size={16} />
              Jaraba Impact Platform
            </div>
            
            <h1 className="text-5xl md:text-7xl font-black text-white leading-tight mb-6">
              Infraestructura digital para el
              <span 
                className="block"
                style={{ color: colors.accent }}
              >
                desarrollo rural
              </span>
            </h1>
            
            <p className="text-xl text-white/80 mb-10 max-w-2xl leading-relaxed">
              La comunidad donde empresas, profesionales e instituciones transforman 
              juntos el futuro digital de los territorios.
            </p>

            <div className="flex flex-wrap gap-4">
              <button
                onClick={() => setCurrentPage('impact')}
                className="flex items-center gap-2 px-8 py-4 rounded-full font-bold text-lg transition-all hover:scale-105 hover:shadow-xl"
                style={{ background: colors.accent, color: 'white' }}
              >
                Conoce nuestro impacto
                <ArrowRight size={20} />
              </button>
              <button
                onClick={() => setCurrentPage('about')}
                className="flex items-center gap-2 px-8 py-4 rounded-full font-bold text-lg transition-all hover:bg-white/20"
                style={{ background: 'rgba(255,255,255,0.1)', color: 'white', border: '2px solid rgba(255,255,255,0.3)' }}
              >
                Sobre nosotros
              </button>
            </div>
          </div>
        </div>

        {/* Scroll Indicator */}
        <div className="absolute bottom-10 left-1/2 transform -translate-x-1/2 text-white/60 text-sm flex flex-col items-center gap-2">
          <span>Scroll</span>
          <div className="w-px h-8 bg-white/30" />
        </div>
      </section>

      {/* Stats */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="grid md:grid-cols-4 gap-8">
            {stats.map((stat, i) => (
              <div 
                key={i}
                className="text-center p-8 rounded-2xl transition-all hover:shadow-xl hover:-translate-y-1"
                style={{ background: colors.lightGray }}
              >
                <stat.icon 
                  size={40} 
                  className="mx-auto mb-4"
                  style={{ color: colors.secondary }}
                />
                <div 
                  className="text-4xl font-black mb-2"
                  style={{ color: colors.primary }}
                >
                  {stat.value}
                </div>
                <div className="text-gray-600">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Triple Motor */}
      <section className="py-24 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 
              className="text-4xl font-black mb-4"
              style={{ color: colors.primary }}
            >
              El Triple Motor Económico
            </h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Un modelo de negocio sostenible y diversificado
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8 mb-16">
            {[
              { pct: '30%', title: 'Motor Institucional', desc: 'Fondos públicos, subvenciones, programas europeos', color: colors.primary },
              { pct: '40%', title: 'Mercado Privado', desc: 'Kits digitales, cursos, membresías SaaS', color: colors.secondary },
              { pct: '30%', title: 'Licencias', desc: 'Franquicias, certificación Método Jaraba', color: colors.accent },
            ].map((motor, i) => (
              <div 
                key={i}
                className="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all hover:-translate-y-1"
              >
                <div 
                  className="text-5xl font-black mb-4"
                  style={{ color: motor.color }}
                >
                  {motor.pct}
                </div>
                <h3 className="text-xl font-bold mb-2 text-gray-900">{motor.title}</h3>
                <p className="text-gray-600">{motor.desc}</p>
              </div>
            ))}
          </div>

          {/* Verticals */}
          <div className="bg-white rounded-2xl p-8 shadow-lg">
            <h3 className="text-2xl font-bold mb-8 text-center" style={{ color: colors.primary }}>
              5 Verticales SaaS
            </h3>
            <div className="flex flex-wrap justify-center gap-4">
              {verticals.map((v, i) => (
                <div 
                  key={i}
                  className="flex items-center gap-3 px-5 py-3 rounded-full text-white font-medium transition-all hover:scale-105"
                  style={{ background: v.color }}
                >
                  <span>{v.name}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Audience Navigation */}
      <section className="py-24 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 
              className="text-4xl font-black mb-4"
              style={{ color: colors.primary }}
            >
              ¿Qué buscas?
            </h2>
            <p className="text-xl text-gray-600">
              Encuentra la información relevante para ti
            </p>
          </div>

          <div className="grid md:grid-cols-4 gap-6">
            {audiences.map((item, i) => (
              <button
                key={i}
                onClick={() => setCurrentPage(item.link)}
                className="group text-left p-8 rounded-2xl border-2 border-gray-200 hover:border-transparent hover:shadow-xl transition-all hover:-translate-y-1"
                style={{ '--hover-bg': colors.primary }}
              >
                <div 
                  className="w-14 h-14 rounded-xl flex items-center justify-center mb-6 transition-colors group-hover:bg-white/20"
                  style={{ background: colors.lightGray }}
                >
                  <item.icon 
                    size={28} 
                    style={{ color: colors.primary }}
                    className="group-hover:text-white transition-colors"
                  />
                </div>
                <h3 className="text-xl font-bold mb-2 text-gray-900 group-hover:text-white transition-colors">
                  {item.title}
                </h3>
                <p className="text-gray-600 group-hover:text-white/80 transition-colors">
                  {item.desc}
                </p>
                <div 
                  className="mt-4 flex items-center gap-2 font-medium transition-colors"
                  style={{ color: colors.accent }}
                >
                  Ver más <ChevronRight size={18} />
                </div>
              </button>
            ))}
          </div>
        </div>
      </section>

      {/* Partners Logos */}
      <section 
        className="py-16"
        style={{ background: colors.lightGray }}
      >
        <div className="max-w-7xl mx-auto px-6">
          <p className="text-center text-gray-500 mb-8 font-medium">
            Colaboramos con instituciones de referencia
          </p>
          <div className="flex flex-wrap justify-center items-center gap-12">
            {['Junta de Andalucía', 'SEPE', 'FUNDAE', 'Google', 'Stripe'].map((partner, i) => (
              <div 
                key={i}
                className="text-gray-400 font-bold text-lg hover:text-gray-600 transition-colors cursor-default"
              >
                {partner}
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};

// ABOUT PAGE
const AboutPage = () => (
  <div>
    {/* Hero */}
    <section 
      className="pt-32 pb-20"
      style={{ background: colors.gradient }}
    >
      <div className="max-w-7xl mx-auto px-6">
        <h1 className="text-5xl font-black text-white mb-6">Sobre Nosotros</h1>
        <p className="text-xl text-white/80 max-w-2xl">
          Plataforma de Ecosistemas Digitales S.L. es la sociedad que opera el 
          Ecosistema Jaraba, transformando territorios a través de la tecnología.
        </p>
      </div>
    </section>

    {/* Content */}
    <section className="py-20 bg-white">
      <div className="max-w-4xl mx-auto px-6">
        {/* Mission */}
        <div className="mb-16">
          <h2 
            className="text-3xl font-bold mb-6"
            style={{ color: colors.primary }}
          >
            Nuestra Misión
          </h2>
          <p className="text-xl text-gray-600 leading-relaxed">
            Democratizar el acceso a la transformación digital para personas, pymes 
            y territorios rurales, eliminando las barreras tecnológicas, económicas 
            y de conocimiento que frenan su desarrollo.
          </p>
        </div>

        {/* Vision */}
        <div className="mb-16">
          <h2 
            className="text-3xl font-bold mb-6"
            style={{ color: colors.primary }}
          >
            Nuestra Visión
          </h2>
          <p className="text-xl text-gray-600 leading-relaxed">
            Ser la infraestructura digital de referencia para el desarrollo rural 
            sostenible en el mundo hispanohablante, donde cada persona y negocio 
            tenga las herramientas para prosperar en la economía digital.
          </p>
        </div>

        {/* Values */}
        <div className="mb-16">
          <h2 
            className="text-3xl font-bold mb-8"
            style={{ color: colors.primary }}
          >
            Nuestros Valores
          </h2>
          <div className="grid md:grid-cols-2 gap-6">
            {[
              { icon: Zap, title: 'Sin Humo', desc: 'Realidad antes que teoría. Solo lo que funciona.' },
              { icon: BarChart3, title: 'Impacto Medible', desc: 'Si no se puede medir, no se puede mejorar.' },
              { icon: Heart, title: 'Accesibilidad', desc: 'Tecnología comprensible para todos.' },
              { icon: Users, title: 'Comunidad', desc: 'Crecemos juntos o no crecemos.' },
            ].map((value, i) => (
              <div 
                key={i}
                className="flex gap-4 p-6 rounded-xl"
                style={{ background: colors.lightGray }}
              >
                <value.icon size={28} style={{ color: colors.secondary }} className="flex-shrink-0" />
                <div>
                  <h3 className="font-bold text-gray-900 mb-1">{value.title}</h3>
                  <p className="text-gray-600">{value.desc}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Company Data */}
        <div 
          className="p-8 rounded-2xl"
          style={{ background: colors.lightGray }}
        >
          <h2 
            className="text-2xl font-bold mb-6"
            style={{ color: colors.primary }}
          >
            Datos Societarios
          </h2>
          <div className="grid md:grid-cols-2 gap-4 text-sm">
            {[
              ['Razón Social', 'Plataforma de Ecosistemas Digitales S.L.'],
              ['CIF', 'B-XXXXXXXX'],
              ['Domicilio Social', 'Calle Ejemplo 123, 14005 Córdoba'],
              ['Fecha Constitución', 'XXXX'],
              ['Representante Legal', 'José Jaraba González'],
              ['Registro Mercantil', 'Córdoba, Tomo XXX, Folio XXX'],
            ].map(([label, value], i) => (
              <div key={i} className="flex justify-between py-2 border-b border-gray-200">
                <span className="text-gray-500">{label}</span>
                <span className="font-medium text-gray-900">{value}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>

    {/* Team Preview */}
    <section className="py-20 bg-gray-50">
      <div className="max-w-7xl mx-auto px-6">
        <h2 
          className="text-3xl font-bold mb-12 text-center"
          style={{ color: colors.primary }}
        >
          Nuestro Equipo
        </h2>

        {/* Founder */}
        <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
          <div className="md:flex">
            <div 
              className="md:w-1/3 h-64 md:h-auto"
              style={{ background: colors.gradient }}
            >
              <div className="h-full flex items-center justify-center">
                <div className="w-32 h-32 rounded-full bg-white/20 flex items-center justify-center">
                  <Users size={48} className="text-white" />
                </div>
              </div>
            </div>
            <div className="md:w-2/3 p-8">
              <div 
                className="text-sm font-medium mb-2"
                style={{ color: colors.secondary }}
              >
                Fundador & CEO
              </div>
              <h3 className="text-2xl font-bold text-gray-900 mb-4">Pepe Jaraba</h3>
              <p className="text-gray-600 leading-relaxed mb-6">
                Más de 30 años de experiencia en transformación digital y gestión de 
                fondos europeos. Ha gestionado más de 100 millones de euros en programas 
                de desarrollo territorial y digitalización empresarial.
              </p>
              <div className="flex gap-4">
                <a 
                  href="#" 
                  className="flex items-center gap-2 text-sm font-medium"
                  style={{ color: colors.primary }}
                >
                  <Linkedin size={18} />
                  LinkedIn
                </a>
                <a 
                  href="#" 
                  className="flex items-center gap-2 text-sm font-medium"
                  style={{ color: colors.primary }}
                >
                  <ExternalLink size={18} />
                  pepejaraba.com
                </a>
              </div>
            </div>
          </div>
        </div>

        {/* Team Grid Placeholder */}
        <div className="grid md:grid-cols-4 gap-6">
          {['CTO', 'COO', 'Head of Product', 'Head of Growth'].map((role, i) => (
            <div key={i} className="bg-white rounded-xl p-6 text-center shadow-md">
              <div 
                className="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center"
                style={{ background: colors.lightGray }}
              >
                <Users size={32} style={{ color: colors.gray }} />
              </div>
              <div className="font-bold text-gray-900 mb-1">Nombre Apellido</div>
              <div className="text-sm text-gray-500">{role}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  </div>
);

// IMPACT PAGE
const ImpactPage = () => {
  const metrics = [
    { value: '2.500+', label: 'Personas formadas', trend: '+23%', color: '#3498DB' },
    { value: '340', label: 'Empleos conseguidos', trend: '+15%', color: '#27AE60' },
    { value: '180', label: 'Negocios digitalizados', trend: '+30%', color: '#9B59B6' },
    { value: '52', label: 'Municipios impactados', trend: 'Nuevo', color: '#E67E22' },
    { value: '4.2M€', label: 'Fondos canalizados', trend: '+40%', color: '#E74C3C' },
    { value: '87%', label: 'Satisfacción usuarios', trend: '+5%', color: '#1ABC9C' },
  ];

  const ods = [
    { num: 4, name: 'Educación de Calidad', color: '#C5192D' },
    { num: 8, name: 'Trabajo Decente', color: '#A21942' },
    { num: 9, name: 'Industria e Innovación', color: '#FD6925' },
    { num: 10, name: 'Reducción Desigualdades', color: '#DD1367' },
    { num: 11, name: 'Ciudades Sostenibles', color: '#FD9D24' },
  ];

  return (
    <div>
      {/* Hero */}
      <section 
        className="pt-32 pb-20"
        style={{ background: colors.gradient }}
      >
        <div className="max-w-7xl mx-auto px-6">
          <h1 className="text-5xl font-black text-white mb-6">Impacto</h1>
          <p className="text-xl text-white/80 max-w-2xl">
            Métricas verificables de nuestro impacto social y económico en personas, 
            negocios y territorios.
          </p>
        </div>
      </section>

      {/* Metrics Dashboard */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <h2 
            className="text-3xl font-bold mb-12 text-center"
            style={{ color: colors.primary }}
          >
            Dashboard de Impacto
          </h2>

          <div className="grid md:grid-cols-3 gap-6 mb-12">
            {metrics.map((m, i) => (
              <div 
                key={i}
                className="p-8 rounded-2xl border-l-4 bg-gray-50 hover:shadow-lg transition-all"
                style={{ borderColor: m.color }}
              >
                <div 
                  className="text-4xl font-black mb-2"
                  style={{ color: m.color }}
                >
                  {m.value}
                </div>
                <div className="text-gray-600 mb-2">{m.label}</div>
                <div 
                  className="inline-flex items-center gap-1 text-sm font-medium px-2 py-1 rounded-full"
                  style={{ background: `${m.color}20`, color: m.color }}
                >
                  <TrendingUp size={14} />
                  {m.trend} vs año anterior
                </div>
              </div>
            ))}
          </div>

          <p className="text-center text-gray-500 text-sm">
            Datos actualizados a Diciembre 2025. Metodología de medición disponible en /transparency
          </p>
        </div>
      </section>

      {/* ODS */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-6">
          <h2 
            className="text-3xl font-bold mb-4 text-center"
            style={{ color: colors.primary }}
          >
            Alineación con ODS
          </h2>
          <p className="text-gray-600 text-center mb-12 max-w-2xl mx-auto">
            Nuestras actividades contribuyen directamente a 5 Objetivos de Desarrollo Sostenible
          </p>

          <div className="flex flex-wrap justify-center gap-4">
            {ods.map((item, i) => (
              <div 
                key={i}
                className="flex items-center gap-3 px-6 py-4 rounded-xl text-white font-medium"
                style={{ background: item.color }}
              >
                <span className="text-2xl font-black">{item.num}</span>
                <span>{item.name}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonials */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <h2 
            className="text-3xl font-bold mb-12 text-center"
            style={{ color: colors.primary }}
          >
            Casos de Éxito
          </h2>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              { name: 'María García', location: 'Jaén', result: 'De desempleada a community manager en 4 meses' },
              { name: 'Antonio López', location: 'Córdoba', result: 'Digitalizó su panadería y aumentó ventas un 40%' },
              { name: 'Carmen Ruiz', location: 'Sevilla', result: 'Lanzó su tienda online desde cero' },
            ].map((case_, i) => (
              <div key={i} className="bg-gray-50 rounded-2xl p-8">
                <Quote size={32} style={{ color: colors.secondary }} className="mb-4" />
                <p className="text-gray-700 mb-6 leading-relaxed">"{case_.result}"</p>
                <div className="flex items-center gap-4">
                  <div 
                    className="w-12 h-12 rounded-full flex items-center justify-center"
                    style={{ background: colors.gradient }}
                  >
                    <Users size={20} className="text-white" />
                  </div>
                  <div>
                    <div className="font-bold text-gray-900">{case_.name}</div>
                    <div className="text-sm text-gray-500">{case_.location}</div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};

// PARTNERS PAGE
const PartnersPage = () => (
  <div>
    {/* Hero */}
    <section 
      className="pt-32 pb-20"
      style={{ background: colors.gradient }}
    >
      <div className="max-w-7xl mx-auto px-6">
        <h1 className="text-5xl font-black text-white mb-6">Partners</h1>
        <p className="text-xl text-white/80 max-w-2xl">
          Colaboramos con instituciones públicas, empresas tecnológicas y 
          organizaciones comprometidas con el desarrollo territorial.
        </p>
      </div>
    </section>

    {/* Partners Grid */}
    <section className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-6">
        <h2 
          className="text-3xl font-bold mb-12"
          style={{ color: colors.primary }}
        >
          Partners Institucionales
        </h2>

        <div className="grid md:grid-cols-4 gap-8 mb-20">
          {['Junta de Andalucía', 'SEPE', 'FUNDAE', 'Diputación de Córdoba', 'Universidad de Córdoba', 'Cámara de Comercio', 'CECO', 'Red CIDES'].map((partner, i) => (
            <div 
              key={i}
              className="h-32 rounded-xl flex items-center justify-center border-2 border-gray-200 hover:border-gray-300 transition-colors"
            >
              <span className="text-gray-400 font-medium">{partner}</span>
            </div>
          ))}
        </div>

        <h2 
          className="text-3xl font-bold mb-12"
          style={{ color: colors.primary }}
        >
          Partners Tecnológicos
        </h2>

        <div className="grid md:grid-cols-4 gap-8 mb-20">
          {['Google Cloud', 'Stripe', 'IONOS', 'Anthropic', 'OpenAI', 'Drupal', 'Make.com', 'Qdrant'].map((partner, i) => (
            <div 
              key={i}
              className="h-32 rounded-xl flex items-center justify-center bg-gray-50 hover:bg-gray-100 transition-colors"
            >
              <span className="text-gray-500 font-medium">{partner}</span>
            </div>
          ))}
        </div>

        {/* CTA */}
        <div 
          className="rounded-2xl p-12 text-center"
          style={{ background: colors.gradient }}
        >
          <h3 className="text-3xl font-bold text-white mb-4">¿Quieres ser partner?</h3>
          <p className="text-white/80 mb-8 max-w-xl mx-auto">
            Únete a nuestra red de aliados y contribuye a la transformación digital 
            del mundo rural.
          </p>
          <button
            className="px-8 py-4 rounded-full font-bold text-lg transition-all hover:scale-105"
            style={{ background: colors.accent, color: 'white' }}
          >
            Contactar para alianza
          </button>
        </div>
      </div>
    </section>
  </div>
);

// PRESS PAGE
const PressPage = () => (
  <div>
    {/* Hero */}
    <section 
      className="pt-32 pb-20"
      style={{ background: colors.gradient }}
    >
      <div className="max-w-7xl mx-auto px-6">
        <h1 className="text-5xl font-black text-white mb-6">Sala de Prensa</h1>
        <p className="text-xl text-white/80 max-w-2xl">
          Recursos oficiales para medios de comunicación.
        </p>
      </div>
    </section>

    {/* Press Kit */}
    <section className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-6">
        <div className="grid md:grid-cols-2 gap-12">
          {/* Press Kit Download */}
          <div>
            <h2 
              className="text-3xl font-bold mb-8"
              style={{ color: colors.primary }}
            >
              Press Kit
            </h2>
            
            <div className="space-y-4">
              {[
                { name: 'Logos (PNG, SVG, AI)', size: '2.4 MB' },
                { name: 'Fotos oficiales equipo', size: '15 MB' },
                { name: 'Biografías ejecutivas', size: '120 KB' },
                { name: 'Fact Sheet 2026', size: '340 KB' },
                { name: 'Guía de estilo de marca', size: '1.8 MB' },
              ].map((file, i) => (
                <button 
                  key={i}
                  className="w-full flex items-center justify-between p-4 rounded-xl border-2 border-gray-200 hover:border-gray-300 transition-colors"
                >
                  <div className="flex items-center gap-4">
                    <FileText size={24} style={{ color: colors.primary }} />
                    <span className="font-medium text-gray-900">{file.name}</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="text-sm text-gray-500">{file.size}</span>
                    <Download size={18} style={{ color: colors.secondary }} />
                  </div>
                </button>
              ))}
            </div>

            <button
              className="mt-6 w-full py-4 rounded-xl font-bold text-white flex items-center justify-center gap-2"
              style={{ background: colors.primary }}
            >
              <Download size={20} />
              Descargar Press Kit Completo (ZIP)
            </button>
          </div>

          {/* Contact */}
          <div>
            <h2 
              className="text-3xl font-bold mb-8"
              style={{ color: colors.primary }}
            >
              Contacto de Prensa
            </h2>
            
            <div 
              className="rounded-2xl p-8"
              style={{ background: colors.lightGray }}
            >
              <p className="text-gray-600 mb-6">
                Para consultas de medios, entrevistas o información adicional, 
                contacte con nuestro equipo de comunicación.
              </p>
              
              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <Mail size={20} style={{ color: colors.primary }} />
                  <span className="font-medium">prensa@plataformadeecosistemas.es</span>
                </div>
                <div className="flex items-center gap-3">
                  <Phone size={20} style={{ color: colors.primary }} />
                  <span className="font-medium">+34 957 000 000</span>
                </div>
                <div className="flex items-center gap-3">
                  <Clock size={20} style={{ color: colors.primary }} />
                  <span className="text-gray-600">Respuesta en menos de 24h</span>
                </div>
              </div>
            </div>

            <h3 
              className="text-xl font-bold mt-12 mb-6"
              style={{ color: colors.primary }}
            >
              Últimas Notas de Prensa
            </h3>

            <div className="space-y-4">
              {[
                { date: '15 Ene 2026', title: 'PED S.L. cierra ronda de financiación de 2M€' },
                { date: '10 Dic 2025', title: 'Lanzamiento de AgroConecta en Andalucía' },
                { date: '28 Nov 2025', title: 'Alianza estratégica con la Junta de Andalucía' },
              ].map((note, i) => (
                <button 
                  key={i}
                  className="w-full text-left p-4 rounded-xl hover:bg-gray-50 transition-colors"
                >
                  <div className="text-sm text-gray-500 mb-1">{note.date}</div>
                  <div className="font-medium text-gray-900">{note.title}</div>
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
);

// CAREERS PAGE
const CareersPage = () => (
  <div>
    {/* Hero */}
    <section 
      className="pt-32 pb-20"
      style={{ background: colors.gradient }}
    >
      <div className="max-w-7xl mx-auto px-6">
        <h1 className="text-5xl font-black text-white mb-6">Trabaja con Nosotros</h1>
        <p className="text-xl text-white/80 max-w-2xl">
          Únete al equipo que está transformando el futuro digital del mundo rural.
        </p>
      </div>
    </section>

    {/* Why Join */}
    <section className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-6">
        <h2 
          className="text-3xl font-bold mb-12 text-center"
          style={{ color: colors.primary }}
        >
          ¿Por qué trabajar en PED?
        </h2>

        <div className="grid md:grid-cols-4 gap-8 mb-20">
          {[
            { icon: Heart, title: 'Impacto Real', desc: 'Tu trabajo mejora la vida de personas y territorios' },
            { icon: Globe, title: 'Trabajo Remoto', desc: '100% flexible, trabaja desde donde quieras' },
            { icon: TrendingUp, title: 'Crecimiento', desc: 'Desarrollo profesional continuo y formación' },
            { icon: Users, title: 'Equipo Increíble', desc: 'Personas apasionadas y comprometidas' },
          ].map((benefit, i) => (
            <div 
              key={i}
              className="text-center p-8 rounded-2xl"
              style={{ background: colors.lightGray }}
            >
              <benefit.icon 
                size={40} 
                className="mx-auto mb-4"
                style={{ color: colors.secondary }}
              />
              <h3 className="font-bold text-gray-900 mb-2">{benefit.title}</h3>
              <p className="text-gray-600 text-sm">{benefit.desc}</p>
            </div>
          ))}
        </div>

        <h2 
          className="text-3xl font-bold mb-8"
          style={{ color: colors.primary }}
        >
          Ofertas Actuales
        </h2>

        <div className="space-y-4 mb-12">
          {[
            { title: 'Senior Drupal Developer', dept: 'Tecnología', location: 'Remoto', type: 'Full-time' },
            { title: 'Product Designer (UX/UI)', dept: 'Producto', location: 'Remoto', type: 'Full-time' },
            { title: 'Customer Success Manager', dept: 'Operaciones', location: 'Híbrido - Córdoba', type: 'Full-time' },
            { title: 'Content & SEO Specialist', dept: 'Marketing', location: 'Remoto', type: 'Part-time' },
          ].map((job, i) => (
            <button 
              key={i}
              className="w-full flex items-center justify-between p-6 rounded-xl border-2 border-gray-200 hover:border-gray-300 hover:shadow-md transition-all"
            >
              <div>
                <h3 className="font-bold text-gray-900 text-lg mb-1">{job.title}</h3>
                <div className="flex gap-4 text-sm text-gray-500">
                  <span>{job.dept}</span>
                  <span>•</span>
                  <span>{job.location}</span>
                  <span>•</span>
                  <span>{job.type}</span>
                </div>
              </div>
              <ChevronRight size={24} style={{ color: colors.primary }} />
            </button>
          ))}
        </div>

        {/* Spontaneous Application */}
        <div 
          className="rounded-2xl p-12 text-center"
          style={{ background: colors.lightGray }}
        >
          <h3 
            className="text-2xl font-bold mb-4"
            style={{ color: colors.primary }}
          >
            ¿No encuentras tu puesto ideal?
          </h3>
          <p className="text-gray-600 mb-8 max-w-xl mx-auto">
            Envíanos tu candidatura espontánea. Siempre estamos buscando talento 
            excepcional que comparta nuestra visión.
          </p>
          <button
            className="px-8 py-4 rounded-full font-bold text-white flex items-center justify-center gap-2 mx-auto"
            style={{ background: colors.primary }}
          >
            <Mail size={20} />
            Enviar candidatura espontánea
          </button>
        </div>
      </div>
    </section>
  </div>
);

// CONTACT PAGE
const ContactPage = () => (
  <div>
    {/* Hero */}
    <section 
      className="pt-32 pb-20"
      style={{ background: colors.gradient }}
    >
      <div className="max-w-7xl mx-auto px-6">
        <h1 className="text-5xl font-black text-white mb-6">Contacto</h1>
        <p className="text-xl text-white/80 max-w-2xl">
          Estamos aquí para ayudarte. Selecciona el tipo de consulta para 
          dirigirte al equipo adecuado.
        </p>
      </div>
    </section>

    {/* Contact Form */}
    <section className="py-20 bg-white">
      <div className="max-w-5xl mx-auto px-6">
        <div className="grid md:grid-cols-2 gap-12">
          {/* Form */}
          <div>
            <h2 
              className="text-2xl font-bold mb-8"
              style={{ color: colors.primary }}
            >
              Envíanos un mensaje
            </h2>

            <form className="space-y-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Tipo de consulta *
                </label>
                <select 
                  className="w-full p-4 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none"
                >
                  <option>Información general</option>
                  <option>Prensa y medios</option>
                  <option>Partners e instituciones</option>
                  <option>Inversores</option>
                  <option>Empleo</option>
                </select>
              </div>

              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Nombre *
                  </label>
                  <input 
                    type="text"
                    className="w-full p-4 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none"
                    placeholder="Tu nombre"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Email *
                  </label>
                  <input 
                    type="email"
                    className="w-full p-4 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none"
                    placeholder="tu@email.com"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Organización
                </label>
                <input 
                  type="text"
                  className="w-full p-4 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none"
                  placeholder="Nombre de tu empresa u organización"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Mensaje *
                </label>
                <textarea 
                  rows={5}
                  className="w-full p-4 rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:outline-none resize-none"
                  placeholder="¿En qué podemos ayudarte?"
                />
              </div>

              <div className="flex items-start gap-3">
                <input type="checkbox" className="mt-1" />
                <span className="text-sm text-gray-600">
                  Acepto la <a href="#" className="underline">política de privacidad</a> y 
                  el tratamiento de mis datos para gestionar esta consulta.
                </span>
              </div>

              <button
                type="submit"
                className="w-full py-4 rounded-xl font-bold text-white flex items-center justify-center gap-2"
                style={{ background: colors.primary }}
              >
                Enviar mensaje
                <ArrowRight size={20} />
              </button>
            </form>
          </div>

          {/* Contact Info */}
          <div>
            <h2 
              className="text-2xl font-bold mb-8"
              style={{ color: colors.primary }}
            >
              Información de contacto
            </h2>

            <div 
              className="rounded-2xl p-8 mb-8"
              style={{ background: colors.lightGray }}
            >
              <div className="space-y-6">
                <div className="flex items-start gap-4">
                  <MapPin size={24} style={{ color: colors.secondary }} className="flex-shrink-0 mt-1" />
                  <div>
                    <div className="font-medium text-gray-900">Dirección</div>
                    <div className="text-gray-600">Calle Ejemplo 123, 14005 Córdoba, España</div>
                  </div>
                </div>
                <div className="flex items-start gap-4">
                  <Phone size={24} style={{ color: colors.secondary }} className="flex-shrink-0 mt-1" />
                  <div>
                    <div className="font-medium text-gray-900">Teléfono</div>
                    <div className="text-gray-600">+34 957 000 000</div>
                  </div>
                </div>
                <div className="flex items-start gap-4">
                  <Mail size={24} style={{ color: colors.secondary }} className="flex-shrink-0 mt-1" />
                  <div>
                    <div className="font-medium text-gray-900">Email general</div>
                    <div className="text-gray-600">info@plataformadeecosistemas.es</div>
                  </div>
                </div>
                <div className="flex items-start gap-4">
                  <Clock size={24} style={{ color: colors.secondary }} className="flex-shrink-0 mt-1" />
                  <div>
                    <div className="font-medium text-gray-900">Horario de atención</div>
                    <div className="text-gray-600">Lunes a Viernes, 9:00 - 18:00</div>
                  </div>
                </div>
              </div>
            </div>

            {/* Map Placeholder */}
            <div 
              className="h-64 rounded-2xl flex items-center justify-center"
              style={{ background: colors.lightGray }}
            >
              <div className="text-center text-gray-500">
                <MapPin size={48} className="mx-auto mb-2 opacity-50" />
                <span>Google Maps Embed</span>
              </div>
            </div>

            {/* SLA */}
            <div className="mt-8 p-6 rounded-xl border-2 border-gray-200">
              <h3 className="font-bold text-gray-900 mb-4">Tiempos de respuesta</h3>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">Información general</span>
                  <span className="font-medium">48h</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Prensa y medios</span>
                  <span className="font-medium">24h</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Partners e instituciones</span>
                  <span className="font-medium">48h</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600">Inversores</span>
                  <span className="font-medium">72h</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
);

// TRANSPARENCY PAGE (Bonus)
const TransparencyPage = () => (
  <div>
    {/* Hero */}
    <section 
      className="pt-32 pb-20"
      style={{ background: colors.gradient }}
    >
      <div className="max-w-7xl mx-auto px-6">
        <h1 className="text-5xl font-black text-white mb-6">Transparencia</h1>
        <p className="text-xl text-white/80 max-w-2xl">
          Información para inversores, administraciones públicas y procesos 
          de due diligence.
        </p>
      </div>
    </section>

    {/* Content */}
    <section className="py-20 bg-white">
      <div className="max-w-4xl mx-auto px-6">
        {/* Documents */}
        <h2 
          className="text-3xl font-bold mb-8"
          style={{ color: colors.primary }}
        >
          Documentos Corporativos
        </h2>

        <div className="space-y-4 mb-16">
          {[
            { name: 'Dossier Corporativo 2026', type: 'PDF', size: '2.4 MB' },
            { name: 'One-Pager para Inversores', type: 'PDF', size: '890 KB' },
            { name: 'Fact Sheet Q4 2025', type: 'PDF', size: '340 KB' },
            { name: 'Cuentas Anuales (Registro Mercantil)', type: 'Enlace', size: '-' },
            { name: 'Código Ético', type: 'PDF', size: '120 KB' },
          ].map((doc, i) => (
            <button 
              key={i}
              className="w-full flex items-center justify-between p-5 rounded-xl border-2 border-gray-200 hover:border-gray-300 transition-colors"
            >
              <div className="flex items-center gap-4">
                <FileText size={24} style={{ color: colors.primary }} />
                <div className="text-left">
                  <div className="font-medium text-gray-900">{doc.name}</div>
                  <div className="text-sm text-gray-500">{doc.type}</div>
                </div>
              </div>
              <div className="flex items-center gap-3">
                {doc.size !== '-' && <span className="text-sm text-gray-500">{doc.size}</span>}
                <Download size={18} style={{ color: colors.secondary }} />
              </div>
            </button>
          ))}
        </div>

        {/* Compliance */}
        <h2 
          className="text-3xl font-bold mb-8"
          style={{ color: colors.primary }}
        >
          Compliance
        </h2>

        <div className="grid md:grid-cols-2 gap-6 mb-16">
          {[
            { icon: Shield, title: 'RGPD Compliance', desc: 'Cumplimiento total del Reglamento General de Protección de Datos' },
            { icon: Award, title: 'WCAG 2.1 AA', desc: 'Accesibilidad web certificada según estándares internacionales' },
            { icon: FileText, title: 'Ley 2/2023', desc: 'Canal de denuncias implementado según normativa' },
            { icon: Check, title: 'Homologación SEPE', desc: 'En proceso de certificación como plataforma de teleformación' },
          ].map((item, i) => (
            <div 
              key={i}
              className="flex gap-4 p-6 rounded-xl"
              style={{ background: colors.lightGray }}
            >
              <item.icon size={28} style={{ color: colors.secondary }} className="flex-shrink-0" />
              <div>
                <h3 className="font-bold text-gray-900 mb-1">{item.title}</h3>
                <p className="text-gray-600 text-sm">{item.desc}</p>
              </div>
            </div>
          ))}
        </div>

        {/* Revenue Model */}
        <h2 
          className="text-3xl font-bold mb-8"
          style={{ color: colors.primary }}
        >
          Modelo de Ingresos
        </h2>

        <div 
          className="rounded-2xl p-8"
          style={{ background: colors.lightGray }}
        >
          <div className="flex items-center justify-around mb-8">
            {[
              { pct: '30%', label: 'Institucional', color: colors.primary },
              { pct: '40%', label: 'Privado', color: colors.secondary },
              { pct: '30%', label: 'Licencias', color: colors.accent },
            ].map((item, i) => (
              <div key={i} className="text-center">
                <div 
                  className="text-4xl font-black mb-2"
                  style={{ color: item.color }}
                >
                  {item.pct}
                </div>
                <div className="text-gray-600">{item.label}</div>
              </div>
            ))}
          </div>
          <p className="text-gray-600 text-center text-sm">
            Modelo Triple Motor: diversificación de ingresos para sostenibilidad a largo plazo.
          </p>
        </div>
      </div>
    </section>
  </div>
);

// ==================== MAIN APP ====================

export default function PEDCorporateWireframe() {
  const [currentPage, setCurrentPage] = useState('home');

  const renderPage = () => {
    switch(currentPage) {
      case 'home': return <HomePage setCurrentPage={setCurrentPage} />;
      case 'about': return <AboutPage />;
      case 'impact': return <ImpactPage />;
      case 'partners': return <PartnersPage />;
      case 'press': return <PressPage />;
      case 'careers': return <CareersPage />;
      case 'contact': return <ContactPage />;
      case 'transparency': return <TransparencyPage />;
      default: return <HomePage setCurrentPage={setCurrentPage} />;
    }
  };

  return (
    <div className="min-h-screen bg-white">
      <Header currentPage={currentPage} setCurrentPage={setCurrentPage} />
      <main>
        {renderPage()}
      </main>
      <Footer setCurrentPage={setCurrentPage} />
    </div>
  );
}
