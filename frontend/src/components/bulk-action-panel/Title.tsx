import * as React from 'react';

interface TitleProps {
  htmlFor: string;
}

const Title: React.FunctionComponent<TitleProps> = ({ htmlFor }) => (
  <label
    htmlFor={htmlFor}
    className='screen-reader-text'
  >
    Select bulk action
  </label>
);

export default Title;
