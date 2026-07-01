import jsPDF from 'jspdf'
import QRCode from 'qrcode'

const COLORS = {
  primary: [22, 65, 75],
  primaryDark: [10, 40, 48],
  text: [40, 40, 40],
  textLight: [110, 110, 110],
  border: [220, 220, 220],
  redBg: [253, 229, 229],
  red: [194, 39, 45],
  cyanBg: [219, 241, 248],
  greenBandeira: [76, 175, 80],
  yellowBandeira: [255, 193, 7],
  redBandeira: [244, 67, 54],
  atencaoBg: [253, 232, 232],
  bxpAccent: [104, 187, 89],
}

const PAGE_W = 210
const MARGIN = 12

function fmtBRL(n) {
  return Number(n || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function fmtDate(iso) {
  if (!iso) return ''
  const [y, m, d] = iso.split('-')
  return `${d}/${m}/${y}`
}

function mesPorExtenso(ref) {
  if (!ref) return ''
  const [ano, mes] = ref.split('-')
  const nomes = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro']
  return `${nomes[parseInt(mes, 10) - 1] || ''} de ${ano}`
}

function setFill(doc, color) { doc.setFillColor(color[0], color[1], color[2]) }
function setText(doc, color) { doc.setTextColor(color[0], color[1], color[2]) }
function setDraw(doc, color) { doc.setDrawColor(color[0], color[1], color[2]) }

function drawLogo(doc, x, y) {
  // Logo placeholder: círculo azul + folha verde + texto BXP ENERGY
  setFill(doc, [80, 140, 200])
  doc.circle(x + 5, y + 5, 5, 'F')
  setFill(doc, COLORS.bxpAccent)
  doc.ellipse(x + 7, y + 4, 3, 2, 'F')
  setText(doc, COLORS.primary)
  doc.setFont('helvetica', 'bold').setFontSize(11)
  doc.text('BXP', x + 12, y + 4)
  doc.setFont('helvetica', 'normal').setFontSize(7)
  doc.text('ENERGY', x + 12, y + 8)
}

function drawHeader(doc, fatura) {
  drawLogo(doc, MARGIN, 8)

  // Box top-right: TOTAL A PAGAR
  const boxX = PAGE_W - MARGIN - 75
  const boxY = 8
  setFill(doc, COLORS.primary)
  doc.rect(boxX, boxY, 75, 14, 'F')
  setText(doc, [255, 255, 255])
  doc.setFont('helvetica', 'normal').setFontSize(7)
  doc.text('TOTAL A PAGAR', boxX + 2, boxY + 4)
  doc.text(fmtDate(fatura.vencimento), boxX + 2, boxY + 9)
  doc.setFont('helvetica', 'bold').setFontSize(14)
  doc.text(fmtBRL(fatura.valores?.valor_com_bxp), boxX + 73, boxY + 11, { align: 'right' })
  setText(doc, COLORS.text)
}

function drawClienteInfo(doc, fatura, yStart) {
  let y = yStart
  const cli = fatura.cliente || {}
  const neo = cli.neoenergia || {}

  doc.setFont('helvetica', 'bold').setFontSize(10)
  setText(doc, COLORS.primary)
  doc.text('CLIENTE', MARGIN, y)
  y += 5

  doc.setFont('helvetica', 'bold').setFontSize(9)
  setText(doc, COLORS.text)
  doc.text(cli.nome_completo || '', MARGIN, y)
  y += 4

  doc.setFont('helvetica', 'normal').setFontSize(8)
  setText(doc, COLORS.textLight)
  const endereco = cli.endereco?.completo || ''
  const wrappedAddress = doc.splitTextToSize(endereco, PAGE_W - 2 * MARGIN)
  doc.text(wrappedAddress, MARGIN, y)
  y += wrappedAddress.length * 3.5 + 4

  // 3 colunas: Classe | Modalidade | Bandeira
  const colW = (PAGE_W - 2 * MARGIN) / 3
  setText(doc, COLORS.textLight)
  doc.setFont('helvetica', 'normal').setFontSize(7)
  doc.text('Classe', MARGIN, y)
  doc.text('Modalidade Tarifária', MARGIN + colW, y)
  doc.text('Bandeira', MARGIN + 2 * colW, y)
  y += 3.5
  setText(doc, COLORS.text)
  doc.setFont('helvetica', 'bold').setFontSize(9)
  doc.text(neo.classe || '—', MARGIN, y)
  doc.text(neo.modalidade_tarifaria || '—', MARGIN + colW, y)
  doc.text(neo.bandeira || '—', MARGIN + 2 * colW, y)

  // Bandeira icon
  const bandColor = neo.bandeira === 'AMARELA' ? COLORS.yellowBandeira
    : (neo.bandeira === 'VERMELHA_1' || neo.bandeira === 'VERMELHA_2') ? COLORS.redBandeira
    : COLORS.greenBandeira
  setFill(doc, bandColor)
  doc.rect(MARGIN + 2 * colW + 24, y - 4, 5, 4, 'F')

  y += 7

  // 3 colunas: Instalação | Código | Cadastro
  setText(doc, COLORS.textLight)
  doc.setFont('helvetica', 'normal').setFontSize(7)
  doc.text('Instalação', MARGIN, y)
  doc.text('Código do Cliente', MARGIN + colW, y)
  doc.text('Cadastro', MARGIN + 2 * colW, y)
  y += 3.5
  setText(doc, COLORS.text)
  doc.setFont('helvetica', 'bold').setFontSize(9)
  doc.text(neo.instalacao || '—', MARGIN, y)
  doc.text(neo.codigo_cliente || '—', MARGIN + colW, y)
  doc.text(neo.cadastro || '—', MARGIN + 2 * colW, y)

  return y + 6
}

function drawComparisonBoxes(doc, fatura, yStart) {
  const w = (PAGE_W - 2 * MARGIN) / 3
  const h = 14
  const valores = fatura.valores || {}

  // Box 1: Sem BXP
  setFill(doc, COLORS.redBg)
  doc.rect(MARGIN, yStart, w - 1, h, 'F')
  setText(doc, COLORS.red)
  doc.setFont('helvetica', 'bold').setFontSize(7)
  doc.text('SEM A BXP ENERGY VOCÊ PAGARIA', MARGIN + 2, yStart + 5)
  doc.setFont('helvetica', 'bold').setFontSize(11)
  const v1 = fmtBRL(valores.valor_sem_bxp)
  doc.text(v1, MARGIN + w - 3, yStart + 11, { align: 'right' })
  // strikethrough
  const v1Width = doc.getTextWidth(v1)
  setDraw(doc, COLORS.red)
  doc.setLineWidth(0.5)
  doc.line(MARGIN + w - 3 - v1Width, yStart + 10, MARGIN + w - 3, yStart + 10)

  // Box 2: Economizou
  const x2 = MARGIN + w
  setFill(doc, COLORS.cyanBg)
  doc.rect(x2, yStart, w - 1, h, 'F')
  setText(doc, COLORS.primary)
  doc.setFont('helvetica', 'bold').setFontSize(7)
  doc.text('COM A BXP ENERGY VOCÊ ECONOMIZOU', x2 + 2, yStart + 5)
  doc.setFont('helvetica', 'bold').setFontSize(11)
  doc.text(fmtBRL(valores.economia), x2 + w - 3, yStart + 11, { align: 'right' })

  // Box 3: Total a pagar
  const x3 = MARGIN + 2 * w
  setFill(doc, COLORS.primary)
  doc.rect(x3, yStart, w, h, 'F')
  setText(doc, [255, 255, 255])
  doc.setFont('helvetica', 'bold').setFontSize(7)
  doc.text('TOTAL A PAGAR', x3 + 2, yStart + 5)
  doc.setFont('helvetica', 'bold').setFontSize(11)
  doc.text(fmtBRL(valores.valor_com_bxp), x3 + w - 3, yStart + 11, { align: 'right' })

  setText(doc, COLORS.text)
  return yStart + h + 6
}

function drawHistoryChart(doc, fatura, yStart) {
  const historico = fatura.historico || []
  const w = PAGE_W - 2 * MARGIN
  const h = 38

  setText(doc, COLORS.textLight)
  doc.setFont('helvetica', 'normal').setFontSize(8)
  doc.text('Histórico de Economia - sem a Bxp e com a Bxp', MARGIN, yStart)

  const chartX = MARGIN
  const chartY = yStart + 3
  const chartW = w
  const chartH = h

  // axis lines
  setDraw(doc, COLORS.border)
  doc.setLineWidth(0.2)
  doc.line(chartX + 6, chartY, chartX + 6, chartY + chartH)
  doc.line(chartX + 6, chartY + chartH, chartX + chartW, chartY + chartH)

  if (!historico.length) {
    setText(doc, COLORS.textLight)
    doc.setFont('helvetica', 'italic').setFontSize(8)
    doc.text('(Sem histórico anterior)', chartX + chartW / 2, chartY + chartH / 2, { align: 'center' })
    return yStart + h + 4
  }

  const maxVal = Math.max(
    ...historico.map((h) => Math.max(Number(h.valor_sem_bxp || 0), Number(h.valor_com_bxp || 0))),
    1500
  )
  const slots = Math.max(12, historico.length)
  const slotW = (chartW - 8) / slots
  const barW = Math.min(slotW * 0.4, 3.5)

  // Y-axis labels (4 levels)
  setText(doc, COLORS.textLight)
  doc.setFont('helvetica', 'normal').setFontSize(6)
  for (let i = 0; i <= 4; i++) {
    const v = Math.round((maxVal * i) / 4)
    const y = chartY + chartH - (chartH * i) / 4
    doc.text(String(v), chartX + 4, y + 1, { align: 'right' })
  }

  // Bars
  historico.forEach((h, i) => {
    const sx = chartX + 8 + i * slotW
    const sem = Number(h.valor_sem_bxp || 0)
    const com = Number(h.valor_com_bxp || 0)
    const semH = (sem / maxVal) * chartH
    const comH = (com / maxVal) * chartH

    setFill(doc, COLORS.red)
    doc.rect(sx, chartY + chartH - semH, barW, semH, 'F')
    setFill(doc, [80, 140, 200])
    doc.rect(sx + barW + 0.5, chartY + chartH - comH, barW, comH, 'F')

    setText(doc, COLORS.textLight)
    doc.setFontSize(5)
    doc.text(h.referencia_mes?.slice(2) || '', sx + barW, chartY + chartH + 3)
  })

  return yStart + h + 6
}

function drawEnvironmentalImpact(doc, fatura, yStart) {
  const tot = fatura.totais_acumulados || {}
  const w = PAGE_W - 2 * MARGIN
  const h = 18

  // Left: já economizou
  const leftW = 65
  setFill(doc, COLORS.cyanBg)
  doc.rect(MARGIN, yStart, leftW, h, 'F')
  setText(doc, COLORS.primary)
  doc.setFont('helvetica', 'bold').setFontSize(7)
  doc.text('COM A BXP', MARGIN + 2, yStart + 5)
  doc.text('ENERGY VOCE JA', MARGIN + 2, yStart + 8)
  doc.text('ECONOMIZOU', MARGIN + 2, yStart + 11)
  doc.setFont('helvetica', 'bold').setFontSize(12)
  doc.text(fmtBRL(tot.total_economizado), MARGIN + leftW - 2, yStart + 12, { align: 'right' })

  // Right: impacto ambiental
  const rightX = MARGIN + leftW + 3
  setText(doc, COLORS.text)
  doc.setFont('helvetica', 'normal').setFontSize(7)
  doc.text('Seu impacto ambiental foi equivalente a', rightX, yStart + 3)

  setText(doc, COLORS.bxpAccent)
  doc.setFont('helvetica', 'bold').setFontSize(11)
  doc.text(String(Number(tot.arvores_preservadas || 0).toFixed(2)), rightX + 6, yStart + 10)
  doc.setFont('helvetica', 'normal').setFontSize(6)
  setText(doc, COLORS.textLight)
  doc.text('árvores preservadas', rightX + 6, yStart + 14)

  setText(doc, COLORS.bxpAccent)
  doc.setFont('helvetica', 'bold').setFontSize(11)
  doc.text(String(Number(tot.credito_carbono_kg || 0).toFixed(2)), rightX + 60, yStart + 10)
  doc.setFont('helvetica', 'normal').setFontSize(6)
  setText(doc, COLORS.textLight)
  doc.text('crédito carbono', rightX + 60, yStart + 14)

  return yStart + h + 5
}

function drawAtencao(doc, yStart) {
  const w = PAGE_W - 2 * MARGIN
  const h = 18
  setFill(doc, COLORS.atencaoBg)
  doc.rect(MARGIN, yStart, w, h, 'F')

  setText(doc, COLORS.red)
  doc.setFont('helvetica', 'bold').setFontSize(8)
  doc.text('ATENÇÃO', MARGIN + 2, yStart + 4)

  setText(doc, COLORS.text)
  doc.setFont('helvetica', 'normal').setFontSize(7)
  const texto = 'O não pagamento da fatura Bxp Energy pode resultar na SUSPENSÃO DO FORNECIMENTO DE ENERGIA pela Celpe. O pagamento dessa fatura não quita débitos anteriores. Em atraso, a fatura terá acréscimo de 2% DE MULTA E 1% DE JUROS pro rata die ao mês sobre o valor em atraso.'
  const linhas = doc.splitTextToSize(texto, w - 4)
  doc.text(linhas, MARGIN + 2, yStart + 8)

  return yStart + h + 4
}

function drawCutLine(doc, y) {
  setDraw(doc, COLORS.textLight)
  doc.setLineWidth(0.3)
  doc.setLineDashPattern([1, 1], 0)
  doc.line(MARGIN, y, PAGE_W - MARGIN, y)
  doc.setLineDashPattern([], 0)
  return y + 4
}

async function drawPaymentStrip(doc, fatura, yStart) {
  const w = PAGE_W - 2 * MARGIN
  let y = yStart
  const cora = fatura.cora || {}

  // Title
  doc.setFont('helvetica', 'bold').setFontSize(9)
  setText(doc, COLORS.text)
  doc.text('Fatura Bxp Energy Aluguel Equipamentos Fotovoltaicos', MARGIN, y)
  doc.setFont('helvetica', 'normal').setFontSize(7)
  doc.text('Pague esse boleto via Pix com o QR code abaixo', PAGE_W - MARGIN, y, { align: 'right' })
  y += 4

  doc.setFont('helvetica', 'normal').setFontSize(7)
  setText(doc, COLORS.textLight)
  doc.text(`Referente a ${mesPorExtenso(fatura.referencia_mes)}`, MARGIN, y)
  y += 5

  // Valor + Vencimento
  doc.setFont('helvetica', 'normal').setFontSize(7)
  setText(doc, COLORS.textLight)
  doc.text('Valor', MARGIN, y)
  doc.text('Vencimento', MARGIN + 30, y)
  y += 4
  doc.setFont('helvetica', 'bold').setFontSize(10)
  setText(doc, COLORS.text)
  doc.text(fmtBRL(fatura.valores?.valor_com_bxp), MARGIN, y)
  doc.text(fmtDate(fatura.vencimento), MARGIN + 30, y)
  y += 5

  // Linha digitável
  if (cora.barcode_digitable_line) {
    doc.setFont('courier', 'normal').setFontSize(9)
    doc.text(cora.barcode_digitable_line, MARGIN, y)
    y += 5
  } else {
    doc.setFont('helvetica', 'italic').setFontSize(7)
    setText(doc, COLORS.textLight)
    doc.text('Linha digitável indisponível (somente PIX nesta cobrança)', MARGIN, y)
    y += 5
  }

  // Barcode + QR side by side
  const barcodeY = y
  const barcodeW = w * 0.6
  const barcodeH = 14
  if (cora.barcode_data) {
    drawBarcode(doc, MARGIN, barcodeY, barcodeW, barcodeH, cora.barcode_data)
  } else {
    drawBarcode(doc, MARGIN, barcodeY, barcodeW, barcodeH, '0'.repeat(44))
  }

  // QR code (right side)
  const qrSize = 32
  const qrX = PAGE_W - MARGIN - qrSize
  if (cora.pix_emv) {
    try {
      const dataUrl = await QRCode.toDataURL(cora.pix_emv, { width: 256, margin: 0 })
      doc.addImage(dataUrl, 'PNG', qrX, barcodeY - 14, qrSize, qrSize)
    } catch (e) {
      // ignore
    }
  } else if (cora.pix_qr_code) {
    const src = cora.pix_qr_code.startsWith('data:') ? cora.pix_qr_code
      : cora.pix_qr_code.startsWith('http') ? cora.pix_qr_code
      : `data:image/png;base64,${cora.pix_qr_code}`
    try {
      doc.addImage(src, 'PNG', qrX, barcodeY - 14, qrSize, qrSize)
    } catch (e) {
      // ignore
    }
  }

  return barcodeY + barcodeH + 3
}

function drawBarcode(doc, x, y, w, h, data) {
  const bars = String(data).padEnd(44, '0')
  const totalUnits = bars.length * 3
  const unit = w / totalUnits
  let cx = x
  setFill(doc, [0, 0, 0])
  for (let i = 0; i < bars.length; i++) {
    const digit = parseInt(bars[i], 10) || 0
    const isBar = i % 2 === 0
    const barW = unit * (digit % 2 === 0 ? 1 : 2)
    if (isBar) doc.rect(cx, y, barW, h, 'F')
    cx += barW + unit * 0.8
    if (cx > x + w) break
  }
}

function drawDetailTables(doc, fatura, yStart) {
  let y = yStart
  const t = fatura.tarifas_snapshot || {}
  const v = fatura.valores || {}
  const kw = Number(fatura.kw_consumidos || 0)

  // Table 1: Sem BXP
  doc.setFont('helvetica', 'bold').setFontSize(9)
  setText(doc, COLORS.primary)
  doc.text('Quanto você pagaria sem a Bxp Energy', MARGIN, y)
  y += 5

  y = drawTable(doc, y, [
    ['Item', 'Quantidade (kw)', 'Valor unitário (Tarifa)', 'Total'],
    ['Energia Consumida Neoenergia', String(kw), fmtBRL(t.tarifa_neoenergia), fmtBRL(kw * t.tarifa_neoenergia)],
    ['Bandeira', '', '', fmtBRL(t.valor_bandeira)],
    ['Fatura mínima Neoenergia', '', '', fmtBRL(t.fatura_minima_neoenergia)],
    ['Total', '', '', fmtBRL(v.valor_sem_bxp), { totalRed: true }],
  ])

  y += 4

  // Table 2: Com BXP
  doc.setFont('helvetica', 'bold').setFontSize(9)
  setText(doc, COLORS.primary)
  doc.text('Quanto você paga com a Bxp Energy', MARGIN, y)
  y += 5

  y = drawTable(doc, y, [
    ['Item', 'Quantidade (kw)', 'Valor unitário (Tarifa)', 'Total'],
    ['Créditos injetados', String(kw), fmtBRL(t.tarifa_bxp), fmtBRL(kw * t.tarifa_bxp)],
    ['Bandeira com desconto', '', '', fmtBRL(t.valor_bandeira_com_desconto)],
    ['Fatura mínima Neoenergia com desconto', '', '', fmtBRL(t.fatura_minima_bxp)],
    ['Total', '', '', fmtBRL(v.valor_com_bxp), { totalBlue: true }],
  ])

  return y + 4
}

function drawTable(doc, yStart, rows) {
  const w = PAGE_W - 2 * MARGIN
  const cols = [w * 0.45, w * 0.18, w * 0.18, w * 0.19]
  const rowH = 6
  let y = yStart

  rows.forEach((row, idx) => {
    if (idx === 0) {
      doc.setFont('helvetica', 'bold').setFontSize(8)
      setText(doc, COLORS.text)
    } else {
      doc.setFont('helvetica', 'normal').setFontSize(8)
      setText(doc, COLORS.text)
    }

    const opts = row[4] || {}
    if (row[0] === 'Total') {
      doc.setFont('helvetica', 'bold').setFontSize(9)
      setText(doc, opts.totalRed ? COLORS.red : (opts.totalBlue ? COLORS.primary : COLORS.text))
    }

    let cx = MARGIN
    for (let i = 0; i < 4; i++) {
      const align = i === 0 ? 'left' : (i === 3 ? 'right' : 'left')
      const tx = align === 'right' ? cx + cols[i] - 1 : cx + 1
      const cell = row[i] || ''
      doc.text(cell, tx, y + 4, { align })
      cx += cols[i]
    }

    if (row[0] === 'Total' && opts.totalRed) {
      // strikethrough total
      const totalText = row[3]
      const tw = doc.getTextWidth(totalText)
      setDraw(doc, COLORS.red)
      doc.setLineWidth(0.5)
      doc.line(MARGIN + w - 1 - tw, y + 3.5, MARGIN + w - 1, y + 3.5)
    }

    setDraw(doc, COLORS.border)
    doc.setLineWidth(0.2)
    doc.line(MARGIN, y + rowH, MARGIN + w, y + rowH)
    y += rowH
  })

  return y
}

function drawExplanations(doc, yStart) {
  let y = yStart
  const w = PAGE_W - 2 * MARGIN
  const colW = w / 2 - 2

  doc.setFont('helvetica', 'bold').setFontSize(9)
  setText(doc, COLORS.primary)
  doc.text('ENTENDA A SUA FATURA', MARGIN, y)
  doc.text('AVISOS IMPORTANTES', MARGIN + colW + 4, y)
  y += 5

  const explicacoes = [
    ['Energia Consumida Neoenergia', 'Essa é a energia convencional da Neoenergia sem desconto'],
    ['Outros Custos Neoenergia', 'Custos cobrados pela distribuidora, não relacionados ao seu consumo de energia'],
    ['Tarifa Neoenergia', 'Tarifa mínima cobrada pela Celpe sobre o consumo da energia renovável'],
    ['Crédito Energia Bxp Energy', 'Valor relativo ao crédito que foi produzido pelos equipamentos fotovoltaicos alugados, ao qual se aplica o desconto.'],
  ]

  let leftY = y
  explicacoes.forEach(([titulo, texto]) => {
    doc.setFont('helvetica', 'bold').setFontSize(8)
    setText(doc, COLORS.text)
    doc.text(titulo, MARGIN, leftY)
    leftY += 3.5
    doc.setFont('helvetica', 'normal').setFontSize(7)
    setText(doc, COLORS.textLight)
    const lines = doc.splitTextToSize(texto, colW)
    doc.text(lines, MARGIN, leftY)
    leftY += lines.length * 3 + 3
  })

  // Avisos
  const avisoX = MARGIN + colW + 4
  doc.setFont('helvetica', 'normal').setFontSize(8)
  setText(doc, COLORS.text)
  doc.text('Prezado(a),', avisoX, y)
  doc.setFont('helvetica', 'normal').setFontSize(7)
  setText(doc, COLORS.textLight)
  const aviso = 'Na fatura Bxp já está inclusa a Fatura da Neoenergia. Você só deve pagar uma fatura esse mês!'
  doc.text(doc.splitTextToSize(aviso, colW), avisoX, y + 4)

  return Math.max(leftY, y + 20) + 4
}

export async function generateBxpFaturaPdf(fatura) {
  const doc = new jsPDF({ unit: 'mm', format: 'a4' })

  // ── Página 1 ──
  drawHeader(doc, fatura)
  let y = 26
  y = drawClienteInfo(doc, fatura, y)
  y = drawComparisonBoxes(doc, fatura, y)
  y = drawHistoryChart(doc, fatura, y)
  y = drawEnvironmentalImpact(doc, fatura, y)
  y = drawAtencao(doc, y)
  y = drawCutLine(doc, y)
  await drawPaymentStrip(doc, fatura, y)

  // ── Página 2 ──
  doc.addPage()
  drawHeader(doc, fatura)
  y = 28
  y = drawDetailTables(doc, fatura, y)
  y = drawExplanations(doc, y)
  // tira de pagamento no rodapé
  const stripY = 250
  drawCutLine(doc, stripY)
  await drawPaymentStrip(doc, fatura, stripY + 4)

  const nome = (fatura.cliente?.nome_completo || 'cliente').replace(/\s+/g, '_').toLowerCase()
  doc.save(`fatura-bxp-${nome}-${fatura.referencia_mes}.pdf`)
}
