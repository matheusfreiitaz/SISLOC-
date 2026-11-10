// Alternância de tema: aplica imediatamente e persiste por usuário no servidor.
window.alternarTema = async function () {
    const escuro = document.documentElement.classList.toggle('dark');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    try {
        await fetch('/conta/tema', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            body: JSON.stringify({ tema: escuro ? 'escuro' : 'claro' }),
        });
    } catch (e) { /* o tema visual já foi aplicado */ }
};
