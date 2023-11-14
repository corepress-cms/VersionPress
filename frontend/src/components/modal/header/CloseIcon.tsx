import * as React from 'react';

interface CloseIconProps {
  onClick(): void;
}

const CloseIcon: React.FunctionComponent<CloseIconProps> = ({ onClick }) => (
  <a
    href='#'
    className='Modal-close'
    onClick={e => { e.stopPropagation(); e.preventDefault(); onClick(); }}
  >
    &times;
  </a>
);

export default CloseIcon;
