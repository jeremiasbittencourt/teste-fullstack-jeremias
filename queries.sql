-- =============================================================================
-- queries.sql — Consultas avançadas do módulo de gestão de pedidos
-- Dialeto: MySQL 8 (usa CTEs e window functions).
-- Convenções: pedidos com soft delete são sempre filtrados (deleted_at IS NULL).
--             "receita" considera apenas pedidos approved + refunded.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- Query A — Ranking de afiliados por receita (top 10)
--
-- Objetivo: listar os 10 afiliados com maior receita líquida, considerando
-- apenas pedidos 'approved' e 'refunded'. Traz nome, quantidade de pedidos,
-- receita bruta, valor reembolsado e receita líquida (bruta - reembolsado).
--
-- Estratégia: agrega por afiliado numa CTE e usa a window function RANK() para
-- o ranking, ordenando pela receita líquida. RANK() (em vez de ROW_NUMBER)
-- deixa explícito eventual empate de receita. O JOIN com orders já restringe
-- aos status de receita, aproveitando o índice (status, created_at).
-- -----------------------------------------------------------------------------
WITH affiliate_revenue AS (
    SELECT
        a.id                                                          AS affiliate_id,
        a.name                                                        AS affiliate_name,
        COUNT(o.id)                                                   AS orders_count,
        SUM(o.total)                                                  AS gross_revenue,
        SUM(CASE WHEN o.status = 'refunded' THEN o.total ELSE 0 END)  AS refunded_amount,
        SUM(o.total) - SUM(CASE WHEN o.status = 'refunded' THEN o.total ELSE 0 END)
                                                                      AS net_revenue
    FROM affiliates a
    JOIN orders o
        ON o.affiliate_id = a.id
       AND o.deleted_at IS NULL
       AND o.status IN ('approved', 'refunded')
    GROUP BY a.id, a.name
)
SELECT
    RANK() OVER (ORDER BY net_revenue DESC) AS ranking,
    affiliate_id,
    affiliate_name,
    orders_count,
    gross_revenue,
    refunded_amount,
    net_revenue
FROM affiliate_revenue
ORDER BY ranking
LIMIT 10;


-- -----------------------------------------------------------------------------
-- Query B — Cohort simplificada (últimos 6 meses)
--
-- Objetivo: para cada um dos últimos 6 meses, total de pedidos novos, aprovados,
-- cancelados e a taxa de aprovação. Meses sem pedidos devem aparecer com zero.
--
-- Estratégia: uma CTE RECURSIVA gera a série dos 6 meses (garante que meses
-- vazios apareçam), outra CTE agrega os pedidos por mês, e um LEFT JOIN entre
-- as duas preenche zeros onde não houve pedido. Sem subqueries aninhadas.
-- O filtro por created_at >= (início de 6 meses atrás) é sargável (usa índice).
-- -----------------------------------------------------------------------------
WITH RECURSIVE months AS (
    SELECT DATE_FORMAT(CURDATE(), '%Y-%m-01') AS month_start
    UNION ALL
    SELECT DATE_SUB(month_start, INTERVAL 1 MONTH)
    FROM months
    WHERE month_start > DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
),
monthly_orders AS (
    SELECT
        DATE_FORMAT(created_at, '%Y-%m-01')                          AS month_start,
        COUNT(*)                                                     AS total_orders,
        SUM(CASE WHEN status = 'approved'  THEN 1 ELSE 0 END)        AS approved_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END)        AS cancelled_orders
    FROM orders
    WHERE deleted_at IS NULL
      AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-01')
)
SELECT
    DATE_FORMAT(m.month_start, '%Y-%m')          AS month,
    COALESCE(mo.total_orders, 0)                 AS total_orders,
    COALESCE(mo.approved_orders, 0)              AS approved_orders,
    COALESCE(mo.cancelled_orders, 0)             AS cancelled_orders,
    CASE
        WHEN COALESCE(mo.total_orders, 0) = 0 THEN 0
        ELSE ROUND(mo.approved_orders / mo.total_orders * 100, 2)
    END                                          AS approval_rate_pct
FROM months m
LEFT JOIN monthly_orders mo ON mo.month_start = m.month_start
ORDER BY m.month_start;


-- -----------------------------------------------------------------------------
-- Query C — Detecção de pedidos duplicados/suspeitos
--
-- Objetivo: encontrar grupos de pedidos suspeitos de duplicidade — mesmo
-- affiliate_id, mesmo valor total (SUM(quantity * unit_price)) e criados no
-- mesmo dia. Retorna o grupo com os IDs envolvidos e o valor duplicado.
--
-- Estratégia: uma CTE calcula o valor real de cada pedido a partir dos itens
-- (não confia no total persistido, conforme enunciado). Em seguida agrupa por
-- (afiliado, dia, valor) e mantém apenas grupos com mais de um pedido.
-- GROUP_CONCAT expõe os IDs suspeitos de cada grupo.
-- -----------------------------------------------------------------------------
WITH order_values AS (
    SELECT
        o.id                                    AS order_id,
        o.affiliate_id,
        DATE(o.created_at)                       AS order_day,
        SUM(oi.quantity * oi.unit_price)         AS order_value
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.deleted_at IS NULL
    GROUP BY o.id, o.affiliate_id, DATE(o.created_at)
)
SELECT
    affiliate_id,
    order_day,
    order_value                                 AS duplicated_value,
    COUNT(*)                                     AS duplicate_count,
    GROUP_CONCAT(order_id ORDER BY order_id)     AS order_ids
FROM order_values
GROUP BY affiliate_id, order_day, order_value
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC, duplicated_value DESC;


-- -----------------------------------------------------------------------------
-- Query D — Produto mais vendido por afiliado
--
-- Objetivo: para cada afiliado, o produto mais vendido em quantidade e o valor
-- total que ele gerou. Em caso de empate na quantidade, vence o produto de
-- maior valor gerado.
--
-- Estratégia: uma CTE agrega quantidade e valor por (afiliado, produto); outra
-- aplica ROW_NUMBER() particionando por afiliado e ordenando por quantidade
-- desc e, como critério de desempate, por valor desc. O filtro rn = 1 seleciona
-- o campeão de cada afiliado.
-- -----------------------------------------------------------------------------
WITH product_sales AS (
    SELECT
        o.affiliate_id,
        oi.product_id,
        SUM(oi.quantity)                    AS total_quantity,
        SUM(oi.quantity * oi.unit_price)    AS total_value
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.deleted_at IS NULL
    GROUP BY o.affiliate_id, oi.product_id
),
ranked AS (
    SELECT
        ps.*,
        ROW_NUMBER() OVER (
            PARTITION BY ps.affiliate_id
            ORDER BY ps.total_quantity DESC, ps.total_value DESC
        ) AS rn
    FROM product_sales ps
)
SELECT
    r.affiliate_id,
    a.name          AS affiliate_name,
    r.product_id,
    p.title         AS product_title,
    r.total_quantity,
    r.total_value
FROM ranked r
JOIN affiliates a ON a.id = r.affiliate_id
JOIN products   p ON p.id = r.product_id
WHERE r.rn = 1
ORDER BY r.total_value DESC;


-- -----------------------------------------------------------------------------
-- Query E — Otimização (diferencial)
--
-- Cenário: "pedidos de um dia específico cujo valor está acima da média de
-- pedidos do próprio afiliado".
--
-- >>> Versão ORIGINAL (lenta) — apenas para referência:
--
--   SELECT *
--   FROM orders o
--   WHERE DATE(o.created_at) = '2024-03-15'
--     AND o.total > (
--         SELECT AVG(o2.total)
--         FROM orders o2
--         WHERE o2.affiliate_id = o.affiliate_id
--     );
--
-- Por que é lenta:
--   1. DATE(o.created_at) = '...' aplica função na coluna → torna o predicado
--      NÃO sargável: o MySQL não consegue usar o índice em created_at e faz
--      full scan.
--   2. Subquery correlacionada no WHERE: o AVG é recalculado por linha avaliada
--      (O(n) execuções), custo quadrático conforme a base cresce (500k pedidos).
--   3. SELECT * traz colunas desnecessárias, aumentando I/O e impedindo
--      covering index.
--
-- O que a versão otimizada resolve:
--   1. Faixa de data sargável (>= início e < fim do dia) → usa o índice
--      (status, created_at) / (affiliate_id, status, created_at).
--   2. A média por afiliado é calculada UMA vez numa CTE e ligada via JOIN,
--      eliminando a correlação linha a linha.
--   3. Projeção explícita das colunas necessárias.
-- -----------------------------------------------------------------------------
WITH affiliate_avg AS (
    SELECT
        affiliate_id,
        AVG(total) AS avg_total
    FROM orders
    WHERE deleted_at IS NULL
    GROUP BY affiliate_id
)
SELECT
    o.id,
    o.affiliate_id,
    o.status,
    o.total,
    o.created_at
FROM orders o
JOIN affiliate_avg aa ON aa.affiliate_id = o.affiliate_id
WHERE o.deleted_at IS NULL
  AND o.created_at >= '2024-03-15 00:00:00'
  AND o.created_at <  '2024-03-16 00:00:00'
  AND o.total > aa.avg_total
ORDER BY o.total DESC;
