/**
 * API helpers for facility endpoints.
 * XServer 本番は /wp-json/roro/v1/* が同一オリジンなので CORS 不要。
 */
import axios from 'axios';

export interface Facility {
  id: number;
  name: string;
  genre: number;
  dist: number;
}

export async function searchFacilities(lat: number, lng: number, rad = 3000) {
  const { data } = await axios.get<Facility[]>(
    `/wp-json/roro/v1/facility-search`,
    { params: { lat, lng, rad } }
  );
  return data;
}
