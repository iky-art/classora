export type Theme = 'light' | 'dark';

export function currentTheme(): Theme {
  return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

export function setTheme(theme: Theme) {
  document.documentElement.setAttribute('data-theme', theme);
  try {
    localStorage.setItem('classora.theme', theme);
  } catch {
    /* penyimpanan tidak tersedia */
  }
}
