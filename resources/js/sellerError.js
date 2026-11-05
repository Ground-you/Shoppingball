export function firstError(error, fallback = '요청에 실패했습니다.') {
    const errors = error.response?.data?.errors;

    if (errors) {
        const first = Object.values(errors)[0];
        if (Array.isArray(first) && first[0]) {
            return first[0];
        }
    }

    return error.response?.data?.message || fallback;
}
