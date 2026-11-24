const buckets = new Map<string, { count: number; resetAt: number }>();

export function checkRateLimit(bucket: string, ip: string, maxRequests: number, windowSeconds: number): boolean {
  const now = Date.now();
  const key = `${bucket}:${ip}`;
  const entry = buckets.get(key);

  if (!entry || now > entry.resetAt) {
    buckets.set(key, { count: 1, resetAt: now + windowSeconds * 1000 });
    return true;
  }

  if (entry.count >= maxRequests) {
    return false;
  }

  entry.count += 1;
  return true;
}
