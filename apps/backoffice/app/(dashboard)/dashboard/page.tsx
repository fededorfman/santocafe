// Dashboard principal — Santo Café Backoffice

export default function DashboardPage() {
  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6" style={{ color: 'var(--color-marron-oscuro)' }}>
        Dashboard
      </h1>
      {/* TODO: Implementar métricas por rol (Admin / Gerente de Ops) */}
      <p className="text-sm" style={{ color: 'var(--color-terracota)' }}>
        Bienvenido al panel de Santo Café. Las métricas se cargarán aquí.
      </p>
    </div>
  );
}
