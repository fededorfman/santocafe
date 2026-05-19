// Página de login passwordless — Santo Café Backoffice

export default function LoginPage() {
  return (
    <main className="min-h-screen flex items-center justify-center" style={{ backgroundColor: 'var(--color-blanco-crema)' }}>
      <div className="w-full max-w-md p-8 rounded-2xl shadow-lg" style={{ backgroundColor: 'white', borderTop: '4px solid var(--color-terracota)' }}>
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold" style={{ color: 'var(--color-marron-oscuro)' }}>
            Santo Café
          </h1>
          <p className="text-sm mt-1" style={{ color: 'var(--color-terracota)' }}>
            Panel de Administración
          </p>
        </div>

        <p className="text-sm text-center mb-6" style={{ color: 'var(--color-marron-oscuro)' }}>
          Ingresá tu email y te enviaremos un código de acceso temporal.
        </p>

        {/* TODO: Reemplazar con componente LoginForm con lógica real */}
        <form className="space-y-4">
          <div>
            <label htmlFor="email" className="block text-sm font-medium mb-1" style={{ color: 'var(--color-marron-oscuro)' }}>
              Email
            </label>
            <input
              id="email"
              type="email"
              placeholder="tu@email.com"
              className="w-full px-4 py-2 rounded-lg border outline-none focus:ring-2"
              style={{ borderColor: 'var(--color-beige-arena)', focusRingColor: 'var(--color-terracota)' }}
            />
          </div>
          <button
            type="submit"
            className="w-full py-2 px-4 rounded-lg font-semibold text-white transition-opacity hover:opacity-90"
            style={{ backgroundColor: 'var(--color-terracota)' }}
          >
            Enviar código de acceso
          </button>
        </form>
      </div>
    </main>
  );
}
