function download(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

export async function exportExcel(filename: string, title: string, headers: string[], rows: (string | number)[][]) {
  const mod = await import('exceljs');
  const ExcelJS = (mod as any).default ?? mod;
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Rekap');
  ws.addRow([title]).font = { bold: true, size: 13 };
  ws.addRow([]);
  ws.addRow(headers).font = { bold: true };
  rows.forEach((r) => ws.addRow(r));
  ws.columns.forEach((c: any) => (c.width = 16));
  const buf = await wb.xlsx.writeBuffer();
  download(new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }), `${filename}.xlsx`);
}

/** PDF A4 landscape, tabel digambar manual agar tanpa plugin tambahan. */
export async function exportPdf(filename: string, title: string, subtitle: string, headers: string[], rows: (string | number)[][]) {
  const { jsPDF } = await import('jspdf');
  const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  const left = 14;
  const width = 297 - left * 2;
  const colW = [width * 0.1, width * 0.34, ...headers.slice(2).map(() => (width * 0.56) / Math.max(headers.length - 2, 1))];
  let y = 18;

  const head = () => {
    doc.setFont('helvetica', 'bold').setFontSize(14).text(title, left, y);
    doc.setFont('helvetica', 'normal').setFontSize(9).text(subtitle, left, y + 5.5);
    y += 13;
    doc.setFont('helvetica', 'bold').setFontSize(9);
    let x = left;
    headers.forEach((h, i) => { doc.text(h, x, y); x += colW[i]; });
    doc.setDrawColor(23, 23, 23).line(left, y + 1.8, left + width, y + 1.8);
    y += 7;
    doc.setFont('helvetica', 'normal');
  };

  head();
  rows.forEach((row) => {
    if (y > 195) { doc.addPage(); y = 18; head(); }
    let x = left;
    row.forEach((cell, i) => { doc.text(String(cell), x, y, { maxWidth: colW[i] - 2 }); x += colW[i]; });
    doc.setDrawColor(216, 213, 204).line(left, y + 1.8, left + width, y + 1.8);
    y += 6.5;
  });
  doc.save(`${filename}.pdf`);
}
