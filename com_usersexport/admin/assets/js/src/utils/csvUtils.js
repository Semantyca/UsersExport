export const convertToCSV = (data) => {
    if (data.length === 0) return '';
    const array = [Object.keys(data[0])].concat(data);
    return array.map(row => Object.values(row).map(value => `"${value}"`).join(',')).join('\n');
};

export const exportCSV = (data, filename = 'users_export.csv') => {
    if (data.length === 0) {
        alert('No data available to export.');
        return;
    }
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
