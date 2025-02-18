import { Statistic } from '../models';
import { adminModuleApi } from './adminModuleApi';
import { ApiRoutesWithoutPrefix } from '@Admin/constants';

export const statisticApi = adminModuleApi.injectEndpoints({
    endpoints: (builder) => ({
        statistics: builder.query<Statistic[], void>({
            query: () => ApiRoutesWithoutPrefix.STATISTICS,
            providesTags: ['Module'],
        }),
        statistic: builder.query<Statistic, string>({
            query: (id) => `${ApiRoutesWithoutPrefix.STATISTICS}/${id}`,
            providesTags: ['Module'],
        }),
    }),
});

export const { useStatisticQuery, useStatisticsQuery } = statisticApi;
